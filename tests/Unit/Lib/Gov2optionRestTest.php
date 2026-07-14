<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\gov2option;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Unit tests slot REST gajah di resolver (#6134 slice D) — driver supabase:
 * pinned > REST gajah > factory XML. PostgREST dipalsukan via Guzzle
 * MockHandler yang di-inject lewat gov2option::$restClient.
 *
 * Fixture app zzpinnedrest: dsnSource.test.xml driver supabase + factory
 * options.xml (Tahun=2020) — dibuat/dibersihkan lokal di test ini;
 * scaffolding dasar (GOV2_VAR_DIR, stub $doc) dari PinnedFixtureTestCase.
 */
class Gov2optionRestTest extends PinnedFixtureTestCase
{
    private const APP_REST = 'zzpinnedrest';

    private MockHandler $mock;

    protected function setUp(): void
    {
        parent::setUp();

        mkdir("{$this->appsDir}/" . self::APP_REST . '/xml', 0777, true);
        file_put_contents("{$this->appsDir}/" . self::APP_REST . '/xml/options.xml', <<<XML
            <options>
              <cluster name="Tahun dan Bulan">
                <item name="Tahun" type="text" value="2020"/>
              </cluster>
            </options>
            XML);
        file_put_contents("{$this->appsDir}/" . self::APP_REST . '/xml/dsnSource.test.xml', <<<XML
            <list>
              <dsn>
                <name>unittest.portal.test</name>
                <driver>supabase</driver>
                <url>https://gajah.unittest.test</url>
                <key>anon-key</key>
              </dsn>
            </list>
            XML);
    }

    protected function tearDown(): void
    {
        self::rrmdir("{$this->appsDir}/" . self::APP_REST);
        parent::tearDown();
    }

    /** Instance driver supabase dgn PostgREST palsu di antrian $queue */
    private function restOpt(Response|RequestException ...$queue): gov2option
    {
        $this->mock = new MockHandler($queue);
        $opt = $this->opt(self::APP_REST);
        $opt->restClient = new Client(['handler' => HandlerStack::create($this->mock)]);

        return $opt;
    }

    private function restResponse(array $rows): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode($rows));
    }

    // ---- REST > factory XML -------------------------------------------------

    public function testRestRowsWinOverFactoryXml(): void
    {
        $rows = $this->sampleRows(self::APP_REST);
        $rows[1]['value'] = '2040';
        $opt = $this->restOpt($this->restResponse($rows));

        $row = $opt->get(['app' => self::APP_REST, 'nama' => 'Tahun']);

        $this->assertEquals('2040', $row['value'], 'REST gajah harus menang atas XML factory (2020)');

        $req = $this->mock->getLastRequest();
        $this->assertStringContainsString('/rest/v1/options', (string) $req->getUri());
        $this->assertStringContainsString('app=eq.' . self::APP_REST, urldecode((string) $req->getUri()));
        $this->assertEquals('anon-key', $req->getHeaderLine('apikey'));
    }

    public function testGetAllViaRest(): void
    {
        $opt = $this->restOpt($this->restResponse($this->sampleRows(self::APP_REST)));

        $all = $opt->getAll(self::APP_REST);

        $this->assertCount(1, $all);
        $this->assertEquals('Tahun dan Bulan', $all[0]['nama']);
        $this->assertArrayNotHasKey('parent_id', $all[0], 'proyeksi select getAll tetap berlaku');
    }

    public function testRestMemoizedPerApp(): void
    {
        $opt = $this->restOpt(
            $this->restResponse($this->sampleRows(self::APP_REST)),
            $this->restResponse([]) // TIDAK boleh terpakai — panggilan kedua dari memo
        );

        $opt->get(['app' => self::APP_REST, 'nama' => 'Tahun']);
        $opt->get(['app' => self::APP_REST, 'nama' => 'Tahun dan Bulan']);

        $this->assertEquals(1, $this->mock->count(), 'get() kedua wajib pakai memo, bukan HTTP baru');
    }

    // ---- fall-through -------------------------------------------------------

    public function testRestNetworkFailureFallsThroughToXml(): void
    {
        $opt = $this->restOpt(new RequestException('gajah down', new Request('GET', 'x')));

        $row = $opt->get(['app' => self::APP_REST, 'nama' => 'Tahun']);

        $this->assertEquals('2020', $row['value'], 'gajah down → factory XML');
        $this->assertStringContainsString('REST gajah gagal', $this->warningsLogged());
    }

    public function testRestHttpErrorFallsThroughToXml(): void
    {
        $opt = $this->restOpt(new Response(500, [], 'oops'));

        $row = $opt->get(['app' => self::APP_REST, 'nama' => 'Tahun']);

        $this->assertEquals('2020', $row['value']);
    }

    public function testRestEmptyRowsFallsThroughToXml(): void
    {
        // Gajah hidup tapi tanpa entri utk app ini ≠ app tanpa config —
        // jaminan boot ter-konfigurasi: factory XML tetap berlaku
        $opt = $this->restOpt($this->restResponse([]));

        $row = $opt->get(['app' => self::APP_REST, 'nama' => 'Tahun']);

        $this->assertEquals('2020', $row['value']);
    }

    // ---- pinned > REST ------------------------------------------------------

    public function testPinnedWinsOverRest(): void
    {
        $this->writePinned(self::APP_REST, $this->sampleRows(self::APP_REST));
        $opt = $this->restOpt($this->restResponse([]));

        $row = $opt->get(['app' => self::APP_REST, 'nama' => 'Tahun']);

        $this->assertEquals('2031', $row['value'], 'pinned = preseden tertinggi');
        $this->assertEquals(1, $this->mock->count(), 'pinned aktif → REST tidak boleh disentuh');
    }

    public function testSqlTierNotEffectiveOnSupabaseDriver(): void
    {
        // Guard autoregistrasi MVC: driver supabase bukan tier SQL
        $opt = $this->restOpt($this->restResponse([]));

        $this->assertFalse($opt->sqlTierEffective(self::APP_REST));
    }
}
