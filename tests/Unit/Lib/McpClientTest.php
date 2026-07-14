<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\mcpClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests mcpClient (#6134 slice D) — handshake initialize + session id,
 * tools/list, tools/call, parsing SSE, auth headers, konvensi error
 * status-bukan-exception. Server gurita dipalsukan via Guzzle MockHandler
 * (pola sama dgn WebdavClientTest/PinnedStoreTest).
 */
class McpClientTest extends TestCase
{
    private MockHandler $mock;

    /** @var array<int, array{request: Request}> */
    private array $history = [];

    private function client(string $authType = 'none', ?string $credential = null, ...$queue): mcpClient
    {
        $this->mock = new MockHandler($queue);
        $this->history = [];
        $stack = HandlerStack::create($this->mock);
        $stack->push(Middleware::history($this->history));

        return new mcpClient(
            'https://gurita.test/mcp',
            $authType,
            $credential,
            new Client(['handler' => $stack, 'http_errors' => false])
        );
    }

    private static function rpcResponse(int $id, array $result, array $headers = []): Response
    {
        return new Response(
            200,
            $headers + ['Content-Type' => 'application/json'],
            (string) json_encode(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result])
        );
    }

    private static function initResponse(array $headers = []): Response
    {
        return self::rpcResponse(1, [
            'protocolVersion' => mcpClient::PROTOCOL_VERSION,
            'capabilities' => [],
            'serverInfo' => ['name' => 'gurita-test'],
        ], $headers);
    }

    private function requestBody(int $index): array
    {
        return json_decode((string) $this->history[$index]['request']->getBody(), true);
    }

    // ---- initialize ---------------------------------------------------------

    public function testInitializeSendsHandshakeThenInitializedNotification(): void
    {
        $client = $this->client('none', null,
            self::initResponse(['Mcp-Session-Id' => 'sess-1']),
            new Response(202)
        );

        $res = $client->initialize();

        $this->assertEquals(200, $res['status']);
        $this->assertNull($res['error']);
        $this->assertEquals('gurita-test', $res['result']['serverInfo']['name']);

        $this->assertCount(2, $this->history);
        $init = $this->requestBody(0);
        $this->assertEquals('initialize', $init['method']);
        $this->assertEquals(mcpClient::PROTOCOL_VERSION, $init['params']['protocolVersion']);

        $notif = $this->requestBody(1);
        $this->assertEquals('notifications/initialized', $notif['method']);
        $this->assertArrayNotHasKey('id', $notif, 'notifikasi tidak boleh membawa id');
        $this->assertEquals(
            'sess-1',
            $this->history[1]['request']->getHeaderLine('Mcp-Session-Id'),
            'session id dari respons initialize wajib dikirim balik'
        );
    }

    public function testInitializeHttpErrorReported(): void
    {
        $client = $this->client('none', null, new Response(404));

        $res = $client->initialize();

        $this->assertEquals(404, $res['status']);
        $this->assertEquals('HTTP 404', $res['error']);
        $this->assertCount(1, $this->history, 'gagal handshake → tanpa notifikasi initialized');
    }

    public function testNetworkErrorStatusZeroNotException(): void
    {
        $client = $this->client('none', null,
            new RequestException('gurita down', new Request('POST', 'x'))
        );

        $res = $client->toolsList();

        $this->assertEquals(0, $res['status']);
        $this->assertStringContainsString('gurita down', $res['error']);
        $this->assertEquals([], $res['tools']);
    }

    // ---- tools/list ---------------------------------------------------------

    public function testToolsListAutoInitializes(): void
    {
        $client = $this->client('none', null,
            self::initResponse(['Mcp-Session-Id' => 'sess-2']),
            new Response(202),
            self::rpcResponse(2, ['tools' => [['name' => 'daftar_proses_bisnis'], ['name' => 'daftar_wilayah']]])
        );

        $res = $client->toolsList();

        $this->assertNull($res['error']);
        $this->assertCount(2, $res['tools']);
        $this->assertEquals('daftar_proses_bisnis', $res['tools'][0]['name']);
        $this->assertEquals('tools/list', $this->requestBody(2)['method']);
        $this->assertEquals('sess-2', $this->history[2]['request']->getHeaderLine('Mcp-Session-Id'));
    }

    public function testToolsListParsesSseResponse(): void
    {
        // Streamable HTTP: server boleh menjawab text/event-stream; event
        // notifikasi lain harus dilewati sampai respons ber-id cocok
        $rpc = json_encode(['jsonrpc' => '2.0', 'id' => 2, 'result' => ['tools' => [['name' => 'sse_tool']]]]);
        $sse = "event: message\ndata: {\"jsonrpc\":\"2.0\",\"method\":\"notifications/progress\"}\n\n"
            . "event: message\ndata: {$rpc}\n\n";

        $client = $this->client('none', null,
            self::initResponse(),
            new Response(202),
            new Response(200, ['Content-Type' => 'text/event-stream'], $sse)
        );

        $res = $client->toolsList();

        $this->assertNull($res['error']);
        $this->assertEquals('sse_tool', $res['tools'][0]['name']);
    }

    // ---- tools/call ---------------------------------------------------------

    public function testToolsCallReturnsResultAndRpcErrorSurfaced(): void
    {
        $client = $this->client('none', null,
            self::initResponse(),
            new Response(202),
            self::rpcResponse(2, ['content' => [['type' => 'text', 'text' => '{"ok":true}']]]),
            new Response(200, ['Content-Type' => 'application/json'],
                (string) json_encode(['jsonrpc' => '2.0', 'id' => 3,
                    'error' => ['code' => -32601, 'message' => 'Method not found']]))
        );

        $ok = $client->toolsCall('daftar_proses_bisnis', ['tahun' => 2026]);
        $this->assertNull($ok['error']);
        $this->assertEquals('tools/call', $this->requestBody(2)['method']);
        $this->assertEquals(['tahun' => 2026], $this->requestBody(2)['params']['arguments']);

        $err = $client->toolsCall('tidak_ada');
        $this->assertEquals('RpcError -32601: Method not found', $err['error']);
        $this->assertNull($err['result']);
    }

    public function testToolsCallToolLevelErrorFlagged(): void
    {
        $client = $this->client('none', null,
            self::initResponse(),
            new Response(202),
            self::rpcResponse(2, ['isError' => true, 'content' => [['type' => 'text', 'text' => 'dataset tidak ditemukan']]])
        );

        $res = $client->toolsCall('daftar_proses_bisnis');

        $this->assertEquals('ToolError: dataset tidak ditemukan', $res['error']);
    }

    // ---- extractPayload -----------------------------------------------------

    public function testExtractPayloadPrefersStructuredContent(): void
    {
        $payload = mcpClient::extractPayload([
            'structuredContent' => ['gov2options' => '1.0'],
            'content' => [['type' => 'text', 'text' => '{"lain":true}']],
        ]);

        $this->assertEquals(['gov2options' => '1.0'], $payload);
    }

    public function testExtractPayloadFallsBackToTextContent(): void
    {
        $payload = mcpClient::extractPayload([
            'content' => [
                ['type' => 'image', 'data' => 'x'],
                ['type' => 'text', 'text' => '{"gov2options":"1.0","clusters":[]}'],
            ],
        ]);

        $this->assertEquals('1.0', $payload['gov2options']);
    }

    public function testExtractPayloadInvalidReturnsNull(): void
    {
        $this->assertNull(mcpClient::extractPayload(null));
        $this->assertNull(mcpClient::extractPayload([]));
        $this->assertNull(mcpClient::extractPayload(['content' => [['type' => 'text', 'text' => 'bukan json']]]));
        $this->assertNull(mcpClient::extractPayload(['content' => [['type' => 'text', 'text' => '"skalar"']]]));
    }

    // ---- auth ---------------------------------------------------------------

    public function testAuthHeadersPerType(): void
    {
        foreach ([
            ['bearer', 'tok123', 'Authorization', 'Bearer tok123'],
            ['basic', 'user:pass', 'Authorization', 'Basic ' . base64_encode('user:pass')],
            ['apikey', 'key123', 'apikey', 'key123'],
        ] as [$type, $credential, $header, $expected]) {
            $client = $this->client($type, $credential, self::initResponse(), new Response(202));
            $client->initialize();

            $this->assertEquals(
                $expected,
                $this->history[0]['request']->getHeaderLine($header),
                "auth_type {$type}"
            );
        }

        $client = $this->client('none', null, self::initResponse(), new Response(202));
        $client->initialize();
        $this->assertEquals('', $this->history[0]['request']->getHeaderLine('Authorization'));
    }

    public function testResponseTooLargeRejected(): void
    {
        $huge = '{"jsonrpc":"2.0","id":1,"result":{"pad":"'
            . str_repeat('a', mcpClient::MAX_BODY_BYTES) . '"}}';
        $client = $this->client('none', null,
            new Response(200, ['Content-Type' => 'application/json'], $huge)
        );

        $res = $client->initialize();

        $this->assertStringContainsString('ResponseTooLarge', (string) $res['error']);
    }
}
