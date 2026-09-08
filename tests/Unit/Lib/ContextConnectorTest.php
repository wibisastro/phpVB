<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\context;
use Gov2lib\DBConnector;
use Gov2lib\dsnSource;
use PHPUnit\Framework\TestCase;

/**
 * Tes DBConnector::__construct($dsn, $dbOverride) dan routing
 * context::connector() / context::reader().
 *
 * Fixture app runtime apps/zzctxdb dengan dsnSource.test.xml (hanya terbaca
 * saat STAGE=test, pola PinnedFixtureTestCase). MeekroDB tidak membuka koneksi
 * sampai query pertama, jadi nama DB yang dipilih bisa di-assert tanpa MySQL.
 */
class ContextConnectorTest extends TestCase
{
    private const APP = 'zzctxdb';
    private const DSN_PORTAL = 'unittest.portal.test';

    private string $appDir;
    private array $prevGlobals = [];

    protected function setUp(): void
    {
        // Binary PHP statis lokal tanpa ekstensi mysqli: DBConnector dan default
        // connect_options MeekroDB memakai dua konstanta mysqli saat konstruksi,
        // padahal koneksi baru dibuka pada query pertama. Shim nilai aslinya
        // supaya pemilihan nama DB tetap bisa diuji tanpa MySQL. db.class.php
        // juga melempar Exception "requires the mysqli extension" di baris atas
        // sementara kelas MeekroDB tetap ter-hoist — telan sekali di sini agar
        // require_once berikutnya di DBConnector menjadi no-op; bila kelas
        // ternyata tidak tersedia, lewati tes alih-alih gagal palsu.
        if (!extension_loaded('mysqli')) {
            defined('MYSQLI_CLIENT_COMPRESS') || define('MYSQLI_CLIENT_COMPRESS', 32);
            defined('MYSQLI_OPT_CONNECT_TIMEOUT') || define('MYSQLI_OPT_CONNECT_TIMEOUT', 0);
            try {
                dsnSource::requireMeekroDB();
            } catch (\Throwable) {
            }
            if (!class_exists(\MeekroDB::class, false)) {
                $this->markTestSkipped('butuh ekstensi mysqli (MeekroDB)');
            }
        }

        $this->appDir = __DIR__ . '/../../../apps/' . self::APP;
        exec('rm -rf ' . escapeshellarg($this->appDir));
        mkdir("{$this->appDir}/xml", 0777, true);
        file_put_contents("{$this->appDir}/xml/dsnSource.test.xml", <<<XML
            <list>
              <dsn>
                <name>master</name>
                <user>u_master</user><pass>p</pass><host>127.0.0.1</host>
                <db>db_master</db>
              </dsn>
              <dsn>
                <name>unittest.portal.test</name>
                <user>u_portal</user><pass>p</pass><host>127.0.0.1</host>
                <db>kl_unittest</db>
              </dsn>
              <dsn>
                <name>gov3_reader</name>
                <user>u_reader</user><pass>p</pass><host>127.0.0.1</host>
              </dsn>
            </list>
            XML);

        foreach (['doc', 'config', 'pageID', 'publickey'] as $key) {
            $this->prevGlobals[$key] = $GLOBALS[$key] ?? null;
        }

        $GLOBALS['pageID'] = self::APP;
        $GLOBALS['doc'] = new class {
            public array $handled = [];

            public function exceptionHandler(string $message): void
            {
                $this->handled[] = $message;
            }
        };
        $GLOBALS['config'] = new \SimpleXMLElement(
            '<config><domain><attr dsn="' . self::DSN_PORTAL . '"/></domain></config>'
        );
        unset($_COOKIE['Gov2Session'], $GLOBALS['publickey']);
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        context::reset();
    }

    protected function tearDown(): void
    {
        context::reset();
        exec('rm -rf ' . escapeshellarg($this->appDir));
        unset($_SERVER['SERVER_NAME'], $_COOKIE['Gov2Session']);

        foreach ($this->prevGlobals as $key => $value) {
            if ($value === null) {
                unset($GLOBALS[$key]);
            } else {
                $GLOBALS[$key] = $value;
            }
        }
    }

    private static function prop(DBConnector $connector, string $name): string
    {
        $property = new \ReflectionProperty($connector->db, $name);

        return (string) $property->getValue($connector->db);
    }

    private function assertNoException(): void
    {
        $this->assertSame([], $GLOBALS['doc']->handled);
    }

    // ---- DBConnector $dbOverride

    public function testTanpaOverrideMemakaiDbDariXml(): void
    {
        $connector = new DBConnector(self::DSN_PORTAL);

        $this->assertSame('kl_unittest', self::prop($connector, 'dbName'));
        $this->assertSame('u_portal', self::prop($connector, 'user'));
        $this->assertNoException();
    }

    public function testOverrideMenggantikanDbXml(): void
    {
        $connector = new DBConnector(self::DSN_PORTAL, 'kab_banglikab');

        $this->assertSame('kab_banglikab', self::prop($connector, 'dbName'));
        $this->assertNoException();
    }

    public function testEntriTanpaDbSahDanDbKosong(): void
    {
        $connector = new DBConnector('gov3_reader');

        $this->assertSame('', self::prop($connector, 'dbName'));
        $this->assertSame('u_reader', self::prop($connector, 'user'));
        $this->assertNoException();
    }

    public function testEntriTanpaDbDenganOverride(): void
    {
        $connector = new DBConnector('gov3_reader', 'prov_jabarprov');

        $this->assertSame('prov_jabarprov', self::prop($connector, 'dbName'));
        $this->assertNoException();
    }

    public function testOverrideTakSahDiabaikan(): void
    {
        foreach (['kl-x', 'KL_KEMENKEU', 'a b', '../x', 'kl_kemenkeu;drop', ' kl_kemenkeu'] as $bad) {
            $connector = new DBConnector(self::DSN_PORTAL, $bad);
            $this->assertSame('kl_unittest', self::prop($connector, 'dbName'), "override '{$bad}' harus diabaikan");

            $connector = new DBConnector('gov3_reader', $bad);
            $this->assertSame('', self::prop($connector, 'dbName'), "override '{$bad}' harus diabaikan");
        }
        $this->assertNoException();
    }

    // ---- context::reader() / connector()

    public function testReaderMenunjukGov3Central(): void
    {
        $connector = context::reader();

        $this->assertSame('u_reader', self::prop($connector, 'user'));
        $this->assertSame(context::CENTRAL_DB, self::prop($connector, 'dbName'));
        $this->assertNoException();
    }

    public function testConnectorTanpaKonteksMemakaiDsnPortal(): void
    {
        $connector = context::connector();

        $this->assertSame('u_portal', self::prop($connector, 'user'));
        $this->assertSame('kl_unittest', self::prop($connector, 'dbName'));
        $this->assertNoException();
    }

    public function testConnectorTanpaAttrDsnJatuhKeMaster(): void
    {
        $GLOBALS['config'] = new \SimpleXMLElement('<config/>');

        $connector = context::connector();

        $this->assertSame('u_master', self::prop($connector, 'user'));
        $this->assertSame('db_master', self::prop($connector, 'dbName'));
    }

    public function testConnectorKonteksPortalLainMemakaiReaderDenganDbKonteks(): void
    {
        context::load(['ctx_scope' => 'kl', 'ctx_slug' => 'kemenkeu', 'ctx_unit' => 'itjen', 'ctx_unit_id' => 5]);

        $connector = context::connector();

        $this->assertTrue(context::isForeign());
        $this->assertSame('u_reader', self::prop($connector, 'user'));
        $this->assertSame('kl_kemenkeu', self::prop($connector, 'dbName'));
        $this->assertNoException();
    }

    public function testConnectorDrillPortalSendiriTetapDsnPortal(): void
    {
        context::load(['ctx_scope' => '', 'ctx_slug' => 'kemenpanrb', 'ctx_unit' => 'itjen', 'ctx_unit_id' => 3]);

        $connector = context::connector();

        $this->assertTrue(context::active());
        $this->assertFalse(context::isForeign());
        $this->assertSame('u_portal', self::prop($connector, 'user'));
        $this->assertSame('kl_unittest', self::prop($connector, 'dbName'));
    }

    public function testConnectorDiCacheDalamSatuRequestDanDibuangSaatReset(): void
    {
        $first = context::connector();
        $this->assertSame($first, context::connector());
        $this->assertNotSame($first, context::reader());

        context::reset();
        $this->assertNotSame($first, context::connector());
    }

    public function testCacheKonektorMengikutiPerubahanKonteks(): void
    {
        $own = context::connector();

        context::load(['ctx_scope' => 'prov', 'ctx_slug' => 'jabarprov']);
        $foreign = context::connector();

        $this->assertNotSame($own, $foreign);
        $this->assertSame('prov_jabarprov', self::prop($foreign, 'dbName'));
    }
}
