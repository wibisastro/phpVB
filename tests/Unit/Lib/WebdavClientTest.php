<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\webdavClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests webdavClient (#6134 slice C) — profil sempit GET/PUT/PROPFIND
 * via Guzzle MockHandler (tanpa jaringan).
 */
class WebdavClientTest extends TestCase
{
    private MockHandler $mock;

    private function client(Response|RequestException ...$queue): webdavClient
    {
        $this->mock = new MockHandler($queue);

        return new webdavClient(
            'https://kambing.test/remote.php/dav/files/instansi/',
            'akun',
            'sandi',
            new Client(['handler' => HandlerStack::create($this->mock), 'http_errors' => false])
        );
    }

    public function testGetReturnsBodyAndEtag(): void
    {
        $dav = $this->client(new Response(200, ['ETag' => '"abc123"'], '{"x":1}'));

        $res = $dav->get('portal-config/p/options/home.json');

        $this->assertEquals(200, $res['status']);
        $this->assertEquals('{"x":1}', $res['body']);
        $this->assertEquals('"abc123"', $res['etag']);

        $req = $this->mock->getLastRequest();
        $this->assertEquals('GET', $req->getMethod());
        $this->assertEquals(
            'https://kambing.test/remote.php/dav/files/instansi/portal-config/p/options/home.json',
            (string) $req->getUri()
        );
    }

    public function testGetSendsIfNoneMatchForRevalidation(): void
    {
        $dav = $this->client(new Response(304));

        $res = $dav->get('f.json', '"abc123"');

        $this->assertEquals(304, $res['status']);
        $this->assertEquals('"abc123"', $this->mock->getLastRequest()->getHeaderLine('If-None-Match'));
    }

    public function testPutCreatesParentCollectionsOnConflict(): void
    {
        // PUT pertama 409 (koleksi induk belum ada) → MKCOL x3 → retry PUT 201
        $dav = $this->client(
            new Response(409),
            new Response(201), // MKCOL portal-config
            new Response(405), // MKCOL portal-config/p (sudah ada = beres)
            new Response(201), // MKCOL portal-config/p/options
            new Response(201, ['ETag' => '"new"'])
        );

        $res = $dav->put('portal-config/p/options/home.json', '{}');

        $this->assertEquals(201, $res['status']);
        $this->assertEquals('"new"', $res['etag']);
        $this->assertEquals(0, $this->mock->count(), 'seluruh urutan MKCOL+retry harus terpakai');
        $this->assertEquals('PUT', $this->mock->getLastRequest()->getMethod());
    }

    public function testPutStraightThroughWhenCollectionExists(): void
    {
        $dav = $this->client(new Response(204));

        $this->assertEquals(204, $dav->put('f.json', 'isi')['status']);
        $this->assertEquals('isi', (string) $this->mock->getLastRequest()->getBody());
    }

    public function testPropfindParsesEntries(): void
    {
        $xml = <<<XML
            <?xml version="1.0"?>
            <d:multistatus xmlns:d="DAV:">
              <d:response>
                <d:href>/remote.php/dav/files/instansi/portal-config/p/options/</d:href>
                <d:propstat><d:prop><d:getetag>"dir"</d:getetag></d:prop></d:propstat>
              </d:response>
              <d:response>
                <d:href>/remote.php/dav/files/instansi/portal-config/p/options/home.json</d:href>
                <d:propstat><d:prop>
                  <d:getetag>"abc"</d:getetag>
                  <d:getlastmodified>Mon, 13 Jul 2026 10:00:00 GMT</d:getlastmodified>
                </d:prop></d:propstat>
              </d:response>
            </d:multistatus>
            XML;
        $dav = $this->client(new Response(207, [], $xml));

        $res = $dav->propfind('portal-config/p/options');

        $this->assertEquals(207, $res['status']);
        $this->assertCount(2, $res['entries']);
        $this->assertEquals('"abc"', $res['entries'][1]['etag']);
        $this->assertStringEndsWith('home.json', $res['entries'][1]['href']);
        $this->assertEquals('1', $this->mock->getLastRequest()->getHeaderLine('Depth'));
    }

    public function testNetworkErrorYieldsStatusZeroNotException(): void
    {
        $dav = $this->client(new RequestException('timeout', new Request('GET', 'x')));

        $res = $dav->get('f.json');

        $this->assertEquals(0, $res['status']);
        $this->assertNull($res['body']);
    }

    public function testBasicAuthAttached(): void
    {
        $dav = $this->client(new Response(200));
        $dav->get('f.json');

        $this->assertEquals(
            'Basic ' . base64_encode('akun:sandi'),
            $this->mock->getLastRequest()->getHeaderLine('Authorization')
        );
    }

    public function testFromEnvNullWithoutConfig(): void
    {
        putenv('GOV2_KAMBING_URL');
        putenv('GOV2_KAMBING_USER');
        putenv('GOV2_KAMBING_PASS');

        $this->assertNull(webdavClient::fromEnv());
    }
}
