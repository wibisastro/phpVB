<?php

namespace Gov2lib;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

/**
 * Client MCP profil sempit untuk gurita (n8n MCP Server Trigger) — #6134 slice D.
 *
 * Hanya subset yang dibutuhkan alur import: initialize (+ notifikasi
 * initialized), tools/list, tools/call — JSON-RPC 2.0 di atas transport
 * Streamable HTTP (POST tunggal; respons application/json ATAU
 * text/event-stream). Bukan library MCP umum: tanpa resources, prompts,
 * sampling, maupun stream server→client berumur panjang.
 *
 * Konvensi error mengikuti webdavClient: semua method mengembalikan array
 * ['status' => int, ...] — status 0 = gagal jaringan, 'error' terisi saat
 * transport/JSON-RPC error; tidak pernah melempar exception ke pemanggil
 * (server gurita = pihak eksternal, kegagalannya bukan fatal portal).
 *
 * Auth mengikuti gov2_connections.auth_type: none | bearer | basic | apikey
 * (apikey = header `apikey`, konvensi Kong gateway ekosistem gov3).
 *
 * @package Gov2lib
 */
class mcpClient
{
    /** Versi protokol MCP yang dinegosiasikan (rilis Streamable HTTP) */
    public const PROTOCOL_VERSION = '2025-03-26';

    /** Cap ukuran body respons — payload import = untrusted input (keputusan 9) */
    public const MAX_BODY_BYTES = 4194304;

    private ClientInterface $client;
    private string $url;
    private string $authType;
    private string $credential;
    private ?string $sessionId = null;
    private bool $initialized = false;
    private int $nextId = 1;

    public function __construct(
        string $url,
        string $authType = 'none',
        ?string $credential = null,
        ?ClientInterface $client = null
    ) {
        $this->url = $url;
        $this->authType = $authType;
        $this->credential = (string) $credential;
        $this->client = $client ?? new Client([
            'connect_timeout' => 5,
            'timeout' => 30,
            'http_errors' => false,
        ]);
    }

    /**
     * Handshake initialize + notifikasi initialized. Menyimpan Mcp-Session-Id
     * dari respons untuk request berikutnya (server stateful mewajibkannya).
     *
     * @return array{status:int, result:?array, error:?string}
     */
    public function initialize(): array
    {
        $res = $this->rpc('initialize', [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities' => (object) [],
            'clientInfo' => ['name' => 'phpVB', 'version' => '2.0'],
        ]);

        if ($res['error'] === null) {
            $this->initialized = true;
            // Notifikasi tanpa id — respons (biasanya 202) tidak dievaluasi;
            // sebagian server menolak tools/* sebelum menerima ini
            $this->rpc('notifications/initialized', [], notification: true);
        }

        return $res;
    }

    /**
     * Inventori tools server — disimpan ke gov2_connections.tools saat registrasi.
     *
     * @return array{status:int, tools:array<int,array>, error:?string}
     */
    public function toolsList(): array
    {
        $res = $this->ensureInitialized() ?? $this->rpc('tools/list', []);

        return [
            'status' => $res['status'],
            'tools' => $res['result']['tools'] ?? [],
            'error' => $res['error'],
        ];
    }

    /**
     * Panggil satu tool. Hasil mentah MCP (content/structuredContent) di
     * 'result' — pakai extractPayload() untuk mengambil payload data.
     *
     * @return array{status:int, result:?array, error:?string}
     */
    public function toolsCall(string $name, array $arguments = []): array
    {
        $res = $this->ensureInitialized() ?? $this->rpc('tools/call', [
            'name' => $name,
            'arguments' => (object) $arguments,
        ]);

        if ($res['error'] === null && !empty($res['result']['isError'])) {
            // Tool-level error MCP: isError=true dengan pesan di content
            $res['error'] = 'ToolError: ' . (self::contentText($res['result']) ?? $name);
        }

        return $res;
    }

    /**
     * Payload data dari hasil tools/call: structuredContent bila ada, kalau
     * tidak content pertama bertipe text di-decode sebagai JSON. Null bila
     * tidak ada payload berbentuk array (pemanggil memvalidasi skema kanonik).
     */
    public static function extractPayload(?array $callResult): ?array
    {
        if (!is_array($callResult)) {
            return null;
        }

        if (is_array($callResult['structuredContent'] ?? null)) {
            return $callResult['structuredContent'];
        }

        $text = self::contentText($callResult);

        if ($text === null) {
            return null;
        }

        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : null;
    }

    /** Teks content pertama bertipe text dari hasil tools/call */
    private static function contentText(array $callResult): ?string
    {
        foreach ($callResult['content'] ?? [] as $item) {
            if (is_array($item) && ($item['type'] ?? '') === 'text' && is_string($item['text'] ?? null)) {
                return $item['text'];
            }
        }

        return null;
    }

    /** @return array{status:int, result:?array, error:?string}|null Null = sudah siap */
    private function ensureInitialized(): ?array
    {
        if ($this->initialized) {
            return null;
        }

        $init = $this->initialize();

        return $init['error'] === null ? null : $init;
    }

    /**
     * Satu round-trip JSON-RPC via POST Streamable HTTP.
     *
     * @return array{status:int, result:?array, error:?string}
     */
    private function rpc(string $method, array $params, bool $notification = false): array
    {
        $message = ['jsonrpc' => '2.0', 'method' => $method];

        // params kosong dihilangkan — "params": [] (array JSON) ditolak
        // server JSON-RPC strict; assoc non-kosong ter-encode sebagai objek
        if ($params !== []) {
            $message['params'] = $params;
        }

        $id = null;

        if (!$notification) {
            $message['id'] = $id = $this->nextId++;
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json, text/event-stream',
        ] + $this->authHeaders();

        if ($this->sessionId !== null) {
            $headers['Mcp-Session-Id'] = $this->sessionId;
        }

        if ($this->initialized) {
            $headers['MCP-Protocol-Version'] = self::PROTOCOL_VERSION;
        }

        try {
            $res = $this->client->request('POST', $this->url, [
                'headers' => $headers,
                'body' => json_encode($message),
            ]);
        } catch (\Throwable $e) {
            return ['status' => 0, 'result' => null, 'error' => $e->getMessage()];
        }

        $status = $res->getStatusCode();

        if ($notification) {
            return ['status' => $status, 'result' => null, 'error' => null];
        }

        if ($sid = $res->getHeaderLine('Mcp-Session-Id')) {
            $this->sessionId = $sid;
        }

        if ($status < 200 || $status >= 300) {
            return ['status' => $status, 'result' => null, 'error' => "HTTP {$status}"];
        }

        $body = $res->getBody()->read(self::MAX_BODY_BYTES + 1);

        if (strlen($body) > self::MAX_BODY_BYTES) {
            return ['status' => $status, 'result' => null,
                'error' => 'ResponseTooLarge: melebihi ' . self::MAX_BODY_BYTES . ' bytes'];
        }

        $message = str_contains($res->getHeaderLine('Content-Type'), 'text/event-stream')
            ? self::sseMessage($body, $id)
            : json_decode($body, true);

        if (!is_array($message)) {
            return ['status' => $status, 'result' => null, 'error' => 'InvalidResponse: bukan JSON-RPC'];
        }

        if (isset($message['error'])) {
            $err = $message['error'];

            return ['status' => $status, 'result' => null,
                'error' => 'RpcError ' . ($err['code'] ?? '?') . ': ' . ($err['message'] ?? '')];
        }

        $result = $message['result'] ?? null;

        return ['status' => $status, 'result' => is_array($result) ? $result : null, 'error' => null];
    }

    /**
     * Respons JSON-RPC ber-id $id dari body SSE: event dipisah baris kosong,
     * data per event = gabungan baris "data:". Event lain (notifikasi/progress)
     * dilewati.
     */
    private static function sseMessage(string $body, ?int $id): ?array
    {
        foreach (preg_split('/\r?\n\r?\n/', $body) as $event) {
            $data = '';

            foreach (preg_split('/\r?\n/', $event) as $line) {
                if (str_starts_with($line, 'data:')) {
                    $data .= ($data === '' ? '' : "\n") . ltrim(substr($line, 5));
                }
            }

            if ($data === '') {
                continue;
            }

            $decoded = json_decode($data, true);

            if (is_array($decoded) && ($decoded['id'] ?? null) === $id
                && (isset($decoded['result']) || isset($decoded['error']))) {
                return $decoded;
            }
        }

        return null;
    }

    /** @return array<string, string> */
    private function authHeaders(): array
    {
        return match ($this->authType) {
            'bearer' => ['Authorization' => 'Bearer ' . $this->credential],
            'basic' => ['Authorization' => 'Basic ' . base64_encode($this->credential)],
            'apikey' => ['apikey' => $this->credential],
            default => [],
        };
    }
}
