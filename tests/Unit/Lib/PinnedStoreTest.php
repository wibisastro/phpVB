<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\gov2option;
use Gov2lib\pinnedStore;
use Gov2lib\webdavClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Unit tests pinnedStore (#6134 slice C) — buildEnvelope (id sintetis),
 * save-to-lower-tier (cache lokal + PUT kambing), sync TTL/etag/
 * stale-while-revalidate. Kambing dipalsukan via Guzzle MockHandler.
 *
 * Scaffolding fixture (GOV2_VAR_DIR, stub $doc, fixture app) di
 * PinnedFixtureTestCase.
 */
class PinnedStoreTest extends PinnedFixtureTestCase
{
    private MockHandler $mock;

    protected function tearDown(): void
    {
        putenv('GOV2_PINNED_TTL');
        parent::tearDown();
    }

    private function store(Response|RequestException ...$queue): pinnedStore
    {
        $this->mock = new MockHandler($queue);
        $dav = new webdavClient(
            'https://kambing.test/dav/files/instansi',
            'akun',
            'sandi',
            new Client(['handler' => HandlerStack::create($this->mock), 'http_errors' => false])
        );

        return new pinnedStore($dav);
    }

    /** Rows bergaya DB (id auto-increment acak) utk buildEnvelope */
    private function dbRows(string $app): array
    {
        return [
            ['id' => 41, 'parent_id' => 0, 'app' => $app, 'nama' => 'Tahun dan Bulan',
             'type' => 'option', 'privilege' => 'admin', 'status' => 'on', 'value' => '',
             'level' => 1, 'level_label' => 'cluster', 'keterangan' => null],
            ['id' => 57, 'parent_id' => 41, 'app' => $app, 'nama' => 'Tahun',
             'type' => 'text', 'privilege' => 'admin', 'status' => 'on', 'value' => '2027',
             'level' => 2, 'level_label' => 'option', 'keterangan' => ''],
        ];
    }

    private function cacheFile(string $app): string
    {
        return (string) gov2option::pinnedPath(self::DSN, $app);
    }

    // ---- buildEnvelope -----------------------------------------------------

    public function testBuildEnvelopeRemapsIdsSequential(): void
    {
        $env = pinnedStore::buildEnvelope($this->dbRows('home'), 'home', 7, 'sql:unittest');

        $this->assertEquals(gov2option::PINNED_VERSION, $env['gov2options']);
        $this->assertEquals('home', $env['meta']['app']);
        $this->assertEquals(7, $env['meta']['saved_by']);
        $this->assertEquals([1, 2], array_column($env['rows'], 'id'));
        $this->assertEquals([0, 1], array_column($env['rows'], 'parent_id')); // 41→1 diikuti anaknya
        $this->assertNull($env['rows'][0]['keterangan']); // null tetap null, bukan ''
    }

    public function testBuildEnvelopePassesResolverValidation(): void
    {
        $env = pinnedStore::buildEnvelope($this->dbRows('home'), 'home');
        file_put_contents($this->cacheFileDir() . '/home.json', json_encode($env));

        $rows = gov2option::pinnedRowsFromFile($this->cacheFile('home'), 'home');

        $this->assertCount(2, $rows);
    }

    private function cacheFileDir(): string
    {
        $dir = dirname($this->cacheFile('home'));

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return $dir;
    }

    // ---- save (save-to-lower-tier) ------------------------------------------

    public function testSaveWithoutKambingWritesCacheAndResolverReadsIt(): void
    {
        // Tanpa env kambing: remote skipped, cache lokal tetap jadi
        $env = pinnedStore::buildEnvelope($this->dbRows(self::APP_XML), self::APP_XML);
        $result = (new pinnedStore())->save(self::DSN, self::APP_XML, $env);

        $this->assertEquals(['remote' => 'skipped', 'cache' => 'ok'], $result);

        $row = $this->opt(self::APP_XML)->get(['app' => self::APP_XML, 'nama' => 'Tahun']);
        $this->assertEquals('2027', $row['value']); // menang atas XML factory (2020)
    }

    public function testSavePutsToKambingAndRecordsEtag(): void
    {
        $store = $this->store(new Response(201, ['ETag' => '"v1"']));
        $env = pinnedStore::buildEnvelope($this->dbRows('home'), 'home');

        $result = $store->save(self::DSN, 'home', $env);

        $this->assertEquals(['remote' => 'ok', 'cache' => 'ok'], $result);

        $req = $this->mock->getLastRequest();
        $this->assertEquals('PUT', $req->getMethod());
        $this->assertStringEndsWith('portal-config/' . self::DSN . '/options/home.json', (string) $req->getUri());

        $meta = json_decode((string) file_get_contents($this->cacheFile('home') . '.sync'), true);
        $this->assertEquals('"v1"', $meta['etag']);
        $this->assertEquals('ok', $meta['status']);
    }

    public function testSaveRemoteFailureStillWritesCache(): void
    {
        $store = $this->store(new Response(507)); // insufficient storage — bukan 404/409, tanpa retry MKCOL

        $result = $store->save(self::DSN, 'home', pinnedStore::buildEnvelope($this->dbRows('home'), 'home'));

        $this->assertEquals('failed:507', $result['remote']);
        $this->assertEquals('ok', $result['cache']);
        $this->assertFileExists($this->cacheFile('home'));
    }

    // ---- sync (TTL + etag + stale-while-revalidate) --------------------------

    public function testSyncPopulatesEmptyCacheFromKambing(): void
    {
        $body = json_encode(pinnedStore::buildEnvelope($this->dbRows(self::APP_XML), self::APP_XML));
        $store = $this->store(new Response(200, ['ETag' => '"r1"'], $body));

        $store->sync(self::DSN, self::APP_XML);

        $this->assertFileExists($this->cacheFile(self::APP_XML));
        $row = $this->opt(self::APP_XML)->get(['app' => self::APP_XML, 'nama' => 'Tahun']);
        $this->assertEquals('2027', $row['value']);
    }

    public function testSyncWithinTtlDoesNotTouchNetwork(): void
    {
        $body = json_encode(pinnedStore::buildEnvelope($this->dbRows('home'), 'home'));
        $store = $this->store(
            new Response(200, ['ETag' => '"r1"'], $body),
            new Response(500) // TIDAK boleh terpakai — sync kedua masih dalam TTL
        );

        $store->sync(self::DSN, 'home');
        $store->sync(self::DSN, 'home'); // di dalam jendela TTL → tanpa HTTP kedua

        $this->assertEquals(1, $this->mock->count(), 'sync kedua tidak boleh menyentuh jaringan');
    }

    public function testSyncRevalidates304KeepsCache(): void
    {
        putenv('GOV2_PINNED_TTL=0'); // paksa revalidasi tiap panggilan
        $body = json_encode(pinnedStore::buildEnvelope($this->dbRows('home'), 'home'));
        $store = $this->store(
            new Response(200, ['ETag' => '"r1"'], $body),
            new Response(304)
        );

        $store->sync(self::DSN, 'home');
        $store->sync(self::DSN, 'home');

        $this->assertEquals('"r1"', $this->mock->getLastRequest()->getHeaderLine('If-None-Match'));
        $this->assertFileExists($this->cacheFile('home'));
        $meta = json_decode((string) file_get_contents($this->cacheFile('home') . '.sync'), true);
        $this->assertEquals('"r1"', $meta['etag']); // etag dipertahankan setelah 304
    }

    public function testSync404RemovesCacheNegativeCached(): void
    {
        putenv('GOV2_PINNED_TTL=0');
        $body = json_encode(pinnedStore::buildEnvelope($this->dbRows('home'), 'home'));
        $store = $this->store(
            new Response(200, ['ETag' => '"r1"'], $body),
            new Response(404)
        );

        $store->sync(self::DSN, 'home');
        $store->sync(self::DSN, 'home'); // pinned dicabut dari kambing

        $this->assertFileDoesNotExist($this->cacheFile('home'));
        $meta = json_decode((string) file_get_contents($this->cacheFile('home') . '.sync'), true);
        $this->assertEquals('missing', $meta['status']);
    }

    public function testSyncNetworkErrorKeepsStaleCache(): void
    {
        putenv('GOV2_PINNED_TTL=0');
        $body = json_encode(pinnedStore::buildEnvelope($this->dbRows('home'), 'home'));
        $store = $this->store(
            new Response(200, ['ETag' => '"r1"'], $body),
            new RequestException('kambing down', new Request('GET', 'x'))
        );

        $store->sync(self::DSN, 'home');
        $store->sync(self::DSN, 'home'); // stale-while-revalidate

        $this->assertFileExists($this->cacheFile('home'), 'cache lama harus tetap tersaji saat kambing down');
        $meta = json_decode((string) file_get_contents($this->cacheFile('home') . '.sync'), true);
        $this->assertEquals('error', $meta['status']);
        $this->assertStringContainsString('revalidasi kambing gagal', $this->warningsLogged());
    }

    public function testSyncFromEnvNoopWithoutKambing(): void
    {
        // Jalur resolver: tanpa GOV2_KAMBING_URL harus no-op instan
        pinnedStore::syncFromEnv(self::DSN, 'home');

        $this->assertFileDoesNotExist($this->cacheFile('home'));
        $this->assertEquals('', $this->warningsLogged());
    }
}
