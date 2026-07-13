<?php

namespace Gov2lib;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

/**
 * Client WebDAV minimal untuk kambing (Nextcloud) — #6134 slice C.
 * Profil sempit: GET / PUT / PROPFIND (+MKCOL untuk memastikan koleksi
 * induk saat PUT pertama). Bukan library DAV umum.
 *
 * Konfigurasi dari env instance (vhost SetEnv / .env — BUKAN options,
 * hindari chicken-egg):
 *   GOV2_KAMBING_URL  — base WebDAV termasuk user, mis.
 *                       https://kambing.gov3.id/remote.php/dav/files/{akun}
 *   GOV2_KAMBING_USER / GOV2_KAMBING_PASS — akun kambing instansi (app password)
 *
 * Semua method mengembalikan array ['status' => int, ...] — status 0 =
 * gagal jaringan (pemanggil menerapkan stale-while-revalidate, bukan fatal).
 *
 * @package Gov2lib
 */
class webdavClient
{
    private ClientInterface $client;
    private string $baseUrl;
    private string $user;
    private string $pass;

    public function __construct(string $baseUrl, string $user, string $pass, ?ClientInterface $client = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->user = $user;
        $this->pass = $pass;
        $this->client = $client ?? new Client([
            'connect_timeout' => 3,
            'timeout' => 10,
            'http_errors' => false,
        ]);
    }

    /** Instance dari env instance; null bila kambing tidak dikonfigurasi */
    public static function fromEnv(?ClientInterface $client = null): ?self
    {
        $url = getenv('GOV2_KAMBING_URL');
        $user = getenv('GOV2_KAMBING_USER');
        $pass = getenv('GOV2_KAMBING_PASS');

        if (!$url || !$user || $pass === false) {
            return null;
        }

        return new self($url, $user, (string) $pass, $client);
    }

    /**
     * GET file. $etag → kirim If-None-Match (revalidasi murah, 304 tanpa body).
     *
     * @return array{status:int, body:?string, etag:?string}
     */
    public function get(string $path, ?string $etag = null): array
    {
        $headers = $etag ? ['If-None-Match' => $etag] : [];

        return $this->request('GET', $path, ['headers' => $headers]);
    }

    /**
     * PUT file. 404/409 (koleksi induk belum ada) → MKCOL berantai + retry sekali.
     *
     * @return array{status:int, body:?string, etag:?string}
     */
    public function put(string $path, string $body): array
    {
        $res = $this->request('PUT', $path, ['body' => $body]);

        if (in_array($res['status'], [404, 409], true)) {
            $this->ensureCollections(dirname($path));
            $res = $this->request('PUT', $path, ['body' => $body]);
        }

        return $res;
    }

    /**
     * PROPFIND depth 1 — daftar isi koleksi.
     *
     * @return array{status:int, entries:array<int, array{href:string, etag:?string, lastmodified:?string}>}
     */
    public function propfind(string $path): array
    {
        $res = $this->request('PROPFIND', $path, ['headers' => ['Depth' => '1']]);
        $entries = [];

        if ($res['status'] === 207 && $res['body'] !== null) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($res['body']);
            libxml_clear_errors();

            if (is_object($xml)) {
                foreach ($xml->children('DAV:')->response as $item) {
                    $prop = $item->propstat->prop ?? null;
                    $entries[] = [
                        'href' => (string) $item->href,
                        'etag' => $prop ? ((string) $prop->getetag ?: null) : null,
                        'lastmodified' => $prop ? ((string) $prop->getlastmodified ?: null) : null,
                    ];
                }
            }
        }

        return ['status' => $res['status'], 'entries' => $entries];
    }

    /** MKCOL satu koleksi; 201 = dibuat, 405 = sudah ada (dua-duanya beres) */
    public function mkcol(string $path): int
    {
        return $this->request('MKCOL', $path)['status'];
    }

    /** Buat rantai koleksi induk (portal-config → {dsn} → options) */
    private function ensureCollections(string $dirPath): void
    {
        $prefix = '';

        foreach (array_filter(explode('/', trim($dirPath, '/.'))) as $segment) {
            $prefix .= ($prefix === '' ? '' : '/') . $segment;
            $this->mkcol($prefix);
        }
    }

    /** @return array{status:int, body:?string, etag:?string} */
    private function request(string $method, string $path, array $options = []): array
    {
        $options['auth'] = [$this->user, $this->pass];

        try {
            $res = $this->client->request($method, $this->baseUrl . '/' . ltrim($path, '/'), $options);
        } catch (\Throwable $e) {
            return ['status' => 0, 'body' => null, 'etag' => null, 'error' => $e->getMessage()];
        }

        return [
            'status' => $res->getStatusCode(),
            'body' => (string) $res->getBody(),
            'etag' => $res->getHeaderLine('ETag') ?: null,
        ];
    }
}
