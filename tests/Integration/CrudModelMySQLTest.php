<?php

declare(strict_types=1);

namespace Tests\Integration;

/**
 * Integration test: crudModel di atas MySQL nyata melalui jalur adapter T2
 * (dsnSource::db() → MeekroAdapter). Skenario CRUD lintas-driver diwarisi
 * dari CrudModelParityBase (fase T4 #6085) — suite yang sama juga jalan di
 * gajah via CrudModelGajahTest.
 *
 * Butuh MySQL test di 127.0.0.1:3310 (user phpvb / phpvb_t2_pass, db
 * phpvb_t2) — otomatis SKIP bila tidak tersedia. Fixture
 * apps/home/xml/dsnSource.test.xml ditulis saat setup dan dihapus lagi
 * (pola dsn*.xml memang gitignored di semua folder).
 */
class CrudModelMySQLTest extends CrudModelParityBase
{
    private const HOST = '127.0.0.1';
    private const PORT = 3310;
    private const USER = 'phpvb';
    private const PASS = 'phpvb_t2_pass';
    private const DB = 'phpvb_t2';

    private static string $dsnFile = __DIR__ . '/../../apps/home/xml/dsnSource.test.xml';
    private static ?\mysqli $mysqli = null;

    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('mysqli')) {
            self::markTestSkipped('ext mysqli tidak tersedia');
        }

        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = @mysqli_connect(self::HOST, self::USER, self::PASS, self::DB, self::PORT);

        if (!$conn) {
            self::markTestSkipped('MySQL test 127.0.0.1:3310 tidak jalan — start dulu (lihat memory laptop-no-php-static-cli)');
        }

        self::$mysqli = $conn;

        file_put_contents(self::$dsnFile, <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <list>
              <dsn>
                <name>master</name>
                <user>phpvb</user>
                <pass>phpvb_t2_pass</pass>
                <host>127.0.0.1</host>
                <port>3310</port>
                <db>phpvb_t2</db>
              </dsn>
              <dsn>
                <name>gajah</name>
                <driver>supabase</driver>
                <url>https://gajah.gov3.id</url>
                <key>test-anon-key</key>
              </dsn>
            </list>
            XML);
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$dsnFile);

        if (self::$mysqli) {
            mysqli_query(self::$mysqli, 'DROP TABLE IF EXISTS member_t2');
            mysqli_query(self::$mysqli, 'DROP TABLE IF EXISTS wilayah_t2');
            mysqli_close(self::$mysqli);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        mysqli_query(self::$mysqli, 'DROP TABLE IF EXISTS member_t2');
        mysqli_query(self::$mysqli, 'DROP TABLE IF EXISTS wilayah_t2');
        mysqli_query(self::$mysqli, <<<SQL
            CREATE TABLE member_t2 (
              id INT AUTO_INCREMENT PRIMARY KEY,
              nama VARCHAR(100) NOT NULL,
              status VARCHAR(10) DEFAULT 'on',
              created_by INT DEFAULT 0,
              created_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);
        mysqli_query(self::$mysqli, <<<SQL
            CREATE TABLE wilayah_t2 (
              id INT AUTO_INCREMENT PRIMARY KEY,
              parent_id INT DEFAULT 0,
              prov_id INT DEFAULT 0,
              nama VARCHAR(100) NOT NULL,
              level VARCHAR(5) DEFAULT '',
              level_label VARCHAR(20) DEFAULT '',
              children INT DEFAULT 0,
              created_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);
    }

    protected function pageId(): string
    {
        return 'home';
    }

    protected function flatTable(): string
    {
        return 'member_t2';
    }

    protected function hierTable(): string
    {
        return 'wilayah_t2';
    }

    protected function model(string $table): object
    {
        return $this->newHarnessModel($table);
    }

    public function testDriverDefaultMeekroDanAdapterTerpakai(): void
    {
        $model = $this->model('member_t2');

        $this->assertEquals('meekro', $model->driverName());
        $this->assertInstanceOf(\Gov2lib\Database\MeekroAdapter::class, $model->db());
        $this->assertInstanceOf(\Gov2lib\Database\MeekroCrudRepository::class, $model->repo());
    }

    public function testDriverSupabaseMenghasilkanAdapterDanRepositorySupabase(): void
    {
        $model = $this->model('member_t2');
        $list = simplexml_load_file(self::$dsnFile);

        $model->credentialDB($list, 'gajah');

        $this->assertEquals('supabase', $model->driverName());
        $this->assertInstanceOf(\Gov2lib\Database\SupabaseAdapter::class, $model->db());
        $this->assertInstanceOf(\Gov2lib\Database\SupabaseCrudRepository::class, $model->repo());

        // Ganti DSN kembali ke meekro → cache adapter ikut ganti
        $model->credentialDB($list, 'master');
        $this->assertInstanceOf(\Gov2lib\Database\MeekroAdapter::class, $model->db());
        $this->assertInstanceOf(\Gov2lib\Database\MeekroCrudRepository::class, $model->repo());
    }
}
