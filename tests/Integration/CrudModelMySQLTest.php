<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration test: crudModel di atas MySQL nyata melalui jalur adapter T2
 * (dsnSource::db() → MeekroAdapter). Verifikasi parity fase T2 #6085.
 *
 * Butuh MySQL test di 127.0.0.1:3310 (user phpvb / phpvb_t2_pass, db
 * phpvb_t2) — otomatis SKIP bila tidak tersedia. Fixture
 * apps/home/xml/dsnSource.test.xml ditulis saat setup dan dihapus lagi
 * (pola dsn*.xml memang gitignored di semua folder).
 */
class CrudModelMySQLTest extends TestCase
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
        $GLOBALS['pageID'] = 'home';
        $GLOBALS['config'] = new \SimpleXMLElement('<config/>');
        $GLOBALS['doc'] = new class {
            public $error = null;
            public function error(...$args): void
            {
                throw new \RuntimeException('doc->error dipanggil: ' . json_encode($args));
            }
            public function exceptionHandler(string $m): void
            {
                throw new \RuntimeException("exceptionHandler: {$m}");
            }
        };

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

    /**
     * crudModel siap uji: tbl dipatok ke tabel test, ses & formfield distub.
     */
    private function model(string $table): object
    {
        $model = new class extends \Gov2lib\crudModel {
            public function breadcrumbRows(): array
            {
                return $this->breadcrumb;
            }
            public function driverName(): string
            {
                return $this->driver;
            }
            public function setTable(string $table): void
            {
                $this->tbl = (object) ['table' => $table];
            }
        };

        $model->setTable($table);
        $ses = (new \ReflectionClass(\Gov2lib\gov2session::class))->newInstanceWithoutConstructor();
        $ses->val = ['account_id' => 7];
        $model->ses = $ses;
        $model->fields = [];
        $model->gov2formfield = new class {
            public function getLevel($fields, $level, $label)
            {
                return $label;
            }
        };

        return $model;
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

    public function testCrudRoundtripFlat(): void
    {
        $model = $this->model('member_t2');

        $id = $model->doAdd(['nama' => 'Andi', 'status' => 'on']);
        $this->assertSame(1, $id);

        $row = $model->doRead($id);
        $this->assertEquals('Andi', $row['nama']);
        $this->assertEquals(7, $row['created_by']);
        $this->assertNotEmpty($row['created_at']);

        $model->doUpdate(['id' => $id, 'nama' => 'Budi', 'cmd' => 'update']);
        $this->assertEquals('Budi', $model->doRead($id)['nama']);

        $model->doDel($id);
        $this->assertNull($model->doRead($id));
    }

    public function testHierarkiAddCountChildrenBrowse(): void
    {
        $model = $this->model('wilayah_t2');

        $provId = $model->doAdd(['nama' => 'Jawa Barat', 'level' => '1', 'level_label' => 'prov']);
        $kabId = $model->doAdd(['nama' => 'Bogor', 'level' => '2', 'level_label' => 'kab', 'parent_id' => $provId]);

        // Anak mewarisi kolom {parent.level_label}_id + counter children parent terupdate
        $kab = $model->doRead($kabId);
        $this->assertEquals($provId, $kab['prov_id']);
        $this->assertEquals($provId, $kab['parent_id']);
        $this->assertEquals(1, (int) $model->doRead($provId)['children']);

        $this->assertEquals(1, $model->doCountChildren($provId)['totalRecord']);

        $rows = $model->doBrowse(1, $provId);
        $this->assertCount(1, $rows);
        $this->assertEquals('Bogor', $rows[0]['nama']);

        // Browse dengan kolom parent custom ({name}_id)
        $rows = $model->doBrowse(1, $provId, 'prov');
        $this->assertCount(1, $rows);
    }

    public function testDelAnakMengoreksiChildrenParent(): void
    {
        $model = $this->model('wilayah_t2');
        $provId = $model->doAdd(['nama' => 'Jabar', 'level' => '1', 'level_label' => 'prov']);
        $kabId = $model->doAdd(['nama' => 'Depok', 'level' => '2', 'level_label' => 'kab', 'parent_id' => $provId]);

        $model->doDel($kabId);

        $this->assertEquals(0, (int) $model->doRead($provId)['children']);
        $this->assertEquals(0, $model->doCountChildren($provId)['totalRecord']);
    }

    public function testSetBreadcrumbMenelusuriParent(): void
    {
        $model = $this->model('wilayah_t2');
        $provId = $model->doAdd(['nama' => 'Jabar', 'level' => '1', 'level_label' => 'prov']);
        $kabId = $model->doAdd(['nama' => 'Depok', 'level' => '2', 'level_label' => 'kab', 'parent_id' => $provId]);

        $model->setBreadcrumb($kabId);
        $crumbs = array_column($model->breadcrumbRows(), 'caption');

        $this->assertEquals(['Depok', 'Jabar'], $crumbs);
    }

    /**
     * Dokumentasi perilaku existing (quirk parity): doUpdate memeriksa
     * in_array('parent_id', columnList) atas ASSOC array metadata — selalu
     * false, jadi jalur hierarkis tidak pernah aktif dan parent_id di-unset
     * dari update. Dipertahankan apa adanya di T2; perbaikannya keputusan
     * terpisah.
     */
    public function testDoUpdateQuirkParentIdTidakPernahHierarkis(): void
    {
        $model = $this->model('wilayah_t2');
        $provId = $model->doAdd(['nama' => 'Jabar', 'level' => '1', 'level_label' => 'prov']);
        $kabId = $model->doAdd(['nama' => 'Depok', 'level' => '2', 'level_label' => 'kab', 'parent_id' => $provId]);

        $model->doUpdate(['id' => $kabId, 'nama' => 'Depok Baru', 'parent_id' => 999]);

        $row = $model->doRead($kabId);
        $this->assertEquals('Depok Baru', $row['nama']);
        $this->assertEquals($provId, $row['parent_id'], 'parent_id di-unset oleh quirk — tidak ikut terupdate');
    }
}
