<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\gov2option;
use Gov2lib\MVC;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the pinned JSON tier (#6134 slice A)
 *
 * Pinned JSON = preseden tertinggi rantai 4 sumber:
 *   pinned JSON > DB (gov2_options) > REST gajah > factory XML
 *
 * Covers:
 * - pinnedRowsFromFile(): envelope validation (version, meta.app, rows shape)
 * - pinnedPath(): path traversal rejection (dsn dari cookie, app dari URL)
 * - get()/getAll(): pinned wins over factory XML and over the DB path;
 *   invalid pinned falls through; no pinned = perilaku lama (regresi BC)
 * - sqlTierEffective() + MVC autoregistration guard (no-op di luar tier SQL)
 *
 * Fixture apps (zzpinnedxml = tier statis + factory XML; zzpinneddb = driver
 * meekro) dibuat runtime di apps/ dan selalu dihapus di tearDown.
 */
class Gov2optionPinnedTest extends TestCase
{
    private const DSN = 'unittest.portal.test';
    private const APP_XML = 'zzpinnedxml';
    private const APP_DB = 'zzpinneddb';

    private string $varDir;
    private string $appsDir;
    private string $errorLog;
    private string|false $prevErrorLog;
    private array $prevGlobals = [];

    protected function setUp(): void
    {
        $this->varDir = sys_get_temp_dir() . '/gov2opt-pinned-' . getmypid();
        $this->appsDir = __DIR__ . '/../../../apps';
        $this->errorLog = $this->varDir . '/php-warnings.log';

        self::rrmdir($this->varDir);
        mkdir($this->varDir, 0777, true);
        putenv('GOV2_VAR_DIR=' . $this->varDir);
        $this->prevErrorLog = ini_set('error_log', $this->errorLog);

        // Fixture tier statis dengan factory XML (rantai: pinned > ... > XML)
        mkdir("{$this->appsDir}/" . self::APP_XML . '/xml', 0777, true);
        file_put_contents("{$this->appsDir}/" . self::APP_XML . '/xml/options.xml', <<<XML
            <options>
              <cluster name="Tahun dan Bulan">
                <item name="Tahun" type="text" value="2020"/>
              </cluster>
              <cluster name="Warna">
                <item name="Tema" type="text" value="biru"/>
              </cluster>
            </options>
            XML);

        // Fixture driver meekro (jalur DB) — dsnSource.test.xml hanya terbaca
        // saat STAGE=test sehingga tidak pernah menyentuh portal nyata
        mkdir("{$this->appsDir}/" . self::APP_DB . '/xml', 0777, true);
        file_put_contents("{$this->appsDir}/" . self::APP_DB . '/xml/dsnSource.test.xml', <<<XML
            <list>
              <dsn>
                <name>unittest.portal.test</name>
                <driver>meekro</driver>
              </dsn>
            </list>
            XML);

        foreach (['doc', 'config', 'pageID', 'self'] as $key) {
            $this->prevGlobals[$key] = $GLOBALS[$key] ?? null;
        }

        // Stub $doc: envRead memasok dsn portal; exceptionHandler merekam
        // panggilan supaya test bisa assert jalur error TIDAK tersentuh
        $GLOBALS['doc'] = new class {
            public array $handled = [];

            public function envRead(string $data): array
            {
                return ['portal' => 'unittest.portal.test'];
            }

            public function exceptionHandler(string $message): void
            {
                $this->handled[] = $message;
            }
        };
    }

    protected function tearDown(): void
    {
        putenv('GOV2_VAR_DIR');

        if ($this->prevErrorLog !== false) {
            ini_set('error_log', $this->prevErrorLog);
        } else {
            ini_restore('error_log');
        }

        self::rrmdir($this->varDir);
        self::rrmdir("{$this->appsDir}/" . self::APP_XML);
        self::rrmdir("{$this->appsDir}/" . self::APP_DB);

        foreach ($this->prevGlobals as $key => $value) {
            if ($value === null) {
                unset($GLOBALS[$key]);
            } else {
                $GLOBALS[$key] = $value;
            }
        }
    }

    private static function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }

    /** Instance segar per skenario — cache driver & pinned per-instance */
    private function opt(string $pageID): gov2option
    {
        $GLOBALS['pageID'] = $pageID;

        return new gov2option();
    }

    /** Rows pinned berbentuk kolom options (id sintetis berurutan) */
    private function sampleRows(string $app): array
    {
        return [
            ['id' => 1, 'parent_id' => 0, 'app' => $app, 'nama' => 'Tahun dan Bulan',
             'type' => 'option', 'privilege' => 'admin', 'status' => 'on', 'value' => '',
             'level' => 1, 'level_label' => 'cluster', 'keterangan' => ''],
            ['id' => 2, 'parent_id' => 1, 'app' => $app, 'nama' => 'Tahun',
             'type' => 'text', 'privilege' => 'admin', 'status' => 'on', 'value' => '2031',
             'level' => 2, 'level_label' => 'option', 'keterangan' => ''],
        ];
    }

    private function writePinned(string $app, array $rows, array $overrides = []): void
    {
        $envelope = array_merge([
            'gov2options' => gov2option::PINNED_VERSION,
            'meta' => ['app' => $app, 'saved_at' => '2026-07-14T00:00:00Z', 'source' => 'test'],
            'rows' => $rows,
        ], $overrides);

        $dir = "{$this->varDir}/options/" . self::DSN;

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents("{$dir}/{$app}.json", json_encode($envelope));
    }

    private function warningsLogged(): string
    {
        return is_file($this->errorLog) ? (string) file_get_contents($this->errorLog) : '';
    }

    // ---- pinnedPath -------------------------------------------------------

    public function testPinnedPathBuildsUnderVarDir(): void
    {
        $path = gov2option::pinnedPath(self::DSN, 'home');

        $this->assertEquals("{$this->varDir}/options/" . self::DSN . '/home.json', $path);
    }

    public function testPinnedPathRejectsTraversalAndInvalidChars(): void
    {
        $this->assertNull(gov2option::pinnedPath('..', 'home'));
        $this->assertNull(gov2option::pinnedPath('portal', '../../etc/passwd'));
        $this->assertNull(gov2option::pinnedPath('portal/../x', 'home'));
        $this->assertNull(gov2option::pinnedPath('', 'home'));
        $this->assertNull(gov2option::pinnedPath('portal', ''));
    }

    // ---- pinnedRowsFromFile: validasi envelope ----------------------------

    public function testMissingFileReturnsNullWithoutWarning(): void
    {
        $this->assertNull(gov2option::pinnedRowsFromFile(gov2option::pinnedPath(self::DSN, 'home'), 'home'));
        $this->assertEquals('', $this->warningsLogged());
    }

    public function testCorruptJsonFallsThroughWithWarning(): void
    {
        $dir = "{$this->varDir}/options/" . self::DSN;
        mkdir($dir, 0777, true);
        file_put_contents("{$dir}/home.json", '{bukan json');

        $this->assertNull(gov2option::pinnedRowsFromFile("{$dir}/home.json", 'home'));
        $this->assertStringContainsString('pinned JSON invalid (envelope)', $this->warningsLogged());
    }

    public function testVersionMismatchFallsThrough(): void
    {
        $this->writePinned('home', $this->sampleRows('home'), ['gov2options' => '2.0']);

        $this->assertNull(gov2option::pinnedRowsFromFile(gov2option::pinnedPath(self::DSN, 'home'), 'home'));
        $this->assertStringContainsString('pinned JSON invalid (envelope)', $this->warningsLogged());
    }

    public function testMetaAppMismatchFallsThrough(): void
    {
        $this->writePinned('home', $this->sampleRows('home'), ['meta' => ['app' => 'aplikasilain']]);

        $this->assertNull(gov2option::pinnedRowsFromFile(gov2option::pinnedPath(self::DSN, 'home'), 'home'));
    }

    public function testMalformedRowsFallThrough(): void
    {
        $this->writePinned('home', [['nama' => 'tanpa id/parent_id/level']]);

        $this->assertNull(gov2option::pinnedRowsFromFile(gov2option::pinnedPath(self::DSN, 'home'), 'home'));
        $this->assertStringContainsString('pinned JSON invalid (rows)', $this->warningsLogged());

        $this->writePinned('home', [], ['rows' => 'bukan-array']);

        $this->assertNull(gov2option::pinnedRowsFromFile(gov2option::pinnedPath(self::DSN, 'home'), 'home'));
    }

    public function testRowMissingSingleColumnInvalidatesFile(): void
    {
        // Row wajib SEMUA kolom options: tanpa 'value' (hasil edit tangan di
        // UI kambing) → MVC intval($row['value']) akan warning + silent
        // disable — validator wajib menolak sebelum sampai ke sana
        $rows = $this->sampleRows('home');
        unset($rows[1]['value']);
        $this->writePinned('home', $rows);

        $this->assertNull(gov2option::pinnedRowsFromFile(gov2option::pinnedPath(self::DSN, 'home'), 'home'));
        $this->assertStringContainsString('pinned JSON invalid (rows)', $this->warningsLogged());
    }

    public function testRowWithNullValuePassesValidation(): void
    {
        // Kolom boleh null (mirror kolom NULL-able jalur SQL) — yang wajib
        // hanya keberadaan key-nya
        $rows = $this->sampleRows('home');
        $rows[1]['keterangan'] = null;
        $this->writePinned('home', $rows);

        $this->assertCount(2, gov2option::pinnedRowsFromFile(gov2option::pinnedPath(self::DSN, 'home'), 'home'));
    }

    public function testUnreadableFileReturnsNullWithoutWarning(): void
    {
        // Gagal baca (permission drift / file lenyap di antara resolve & baca)
        // = setara tidak ada pinned: null TANPA warning "invalid" menyesatkan
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('root selalu bisa baca — chmod 0000 tidak berefek');
        }

        $this->writePinned('home', $this->sampleRows('home'));
        $file = gov2option::pinnedPath(self::DSN, 'home');
        chmod($file, 0000);

        $this->assertNull(gov2option::pinnedRowsFromFile($file, 'home'));
        $this->assertEquals('', $this->warningsLogged());

        chmod($file, 0644); // supaya tearDown bisa bersih-bersih
    }

    public function testMatchCaseInsensitiveLikeMysqlCollation(): void
    {
        // Snapshot DB verbatim bisa membawa status 'ON'/'On' — perbandingan
        // harus ci seperti collation *_ci yang digantikan jalur pinned
        $rows = $this->sampleRows(self::APP_XML);
        $rows[0]['status'] = 'ON';
        $rows[1]['status'] = 'On';
        $this->writePinned(self::APP_XML, $rows);

        $all = $this->opt(self::APP_XML)->getAll(self::APP_XML);
        $this->assertEquals(['Tahun dan Bulan'], array_column($all, 'nama'));

        $row = $this->opt(self::APP_XML)->get(
            ['app' => self::APP_XML, 'nama' => 'Tahun', 'status' => 'on']
        , 'and', ['id', 'value']);
        $this->assertEquals('2031', $row['value']);
    }

    public function testValidEnvelopeReturnsRows(): void
    {
        $this->writePinned('home', $this->sampleRows('home'));

        $rows = gov2option::pinnedRowsFromFile(gov2option::pinnedPath(self::DSN, 'home'), 'home');

        $this->assertCount(2, $rows);
        $this->assertEquals('2031', $rows[1]['value']);
        $this->assertEquals('', $this->warningsLogged());
    }

    // ---- get()/getAll(): rantai resolusi ----------------------------------

    public function testGetPinnedWinsOverFactoryXml(): void
    {
        $this->writePinned(self::APP_XML, $this->sampleRows(self::APP_XML));

        $row = $this->opt(self::APP_XML)->get(['app' => self::APP_XML, 'nama' => 'Tahun']);

        $this->assertEquals('2031', $row['value']); // XML factory bilang 2020
    }

    public function testGetPinnedWinsOverDbPath(): void
    {
        // Driver meekro tanpa server DB: pinned harus menang SEBELUM jalur DB
        // tersentuh — tidak ada exception dan tidak ada exceptionHandler
        $this->writePinned(self::APP_DB, $this->sampleRows(self::APP_DB));

        $row = $this->opt(self::APP_DB)->get(['app' => self::APP_DB, 'nama' => 'Tahun']);

        $this->assertEquals('2031', $row['value']);
        $this->assertEquals([], $GLOBALS['doc']->handled);
    }

    public function testGetPinnedMissIsNotPerEntryFallback(): void
    {
        // Short-circuit per-sumber: pinned aktif tapi entry tidak ada → null,
        // BUKAN jatuh ke XML (yang punya 'Tema')
        $this->writePinned(self::APP_XML, $this->sampleRows(self::APP_XML));

        $this->assertNull($this->opt(self::APP_XML)->get(['app' => self::APP_XML, 'nama' => 'Tema']));
    }

    public function testNoAppKeyPinnedMissFallsToLowerTier(): void
    {
        // Pencarian lintas-app legacy ($where TANPA 'app', mis. krisna_*):
        // pinned current miss → TETAP jatuh ke tier bawah (di sini XML),
        // beda dengan lookup ber-'app' yang short-circuit
        $this->writePinned(self::APP_XML, $this->sampleRows(self::APP_XML));

        $row = $this->opt(self::APP_XML)->get(['nama' => 'Tema']);

        $this->assertEquals('biru', $row['value']);
    }

    public function testNoAppKeyFallsBackToHomePinned(): void
    {
        // Konvensi cross-calling #6134: miss di scope current → cek pinned home
        $this->writePinned(self::APP_XML, $this->sampleRows(self::APP_XML));
        $this->writePinned('home', [
            ['id' => 1, 'parent_id' => 0, 'app' => 'home', 'nama' => 'Endpoint',
             'type' => 'option', 'privilege' => 'admin', 'status' => 'on', 'value' => '',
             'level' => 1, 'level_label' => 'cluster', 'keterangan' => ''],
            ['id' => 2, 'parent_id' => 1, 'app' => 'home', 'nama' => 'krisna_authorize',
             'type' => 'text', 'privilege' => 'admin', 'status' => 'on',
             'value' => 'https://sso.contoh.go.id', 'level' => 2,
             'level_label' => 'option', 'keterangan' => ''],
        ]);

        $row = $this->opt(self::APP_XML)->get(['nama' => 'krisna_authorize']);

        $this->assertEquals('https://sso.contoh.go.id', $row['value']);
    }

    public function testNoAppKeyLocalPinnedShadowsHome(): void
    {
        // Entry sama di pinned current & home → lokal menang (shadowing #6134)
        $this->writePinned(self::APP_XML, $this->sampleRows(self::APP_XML)); // Tahun=2031
        $homeRows = $this->sampleRows('home');
        $homeRows[1]['value'] = '1999';
        $this->writePinned('home', $homeRows);

        $row = $this->opt(self::APP_XML)->get(['nama' => 'Tahun']);

        $this->assertEquals('2031', $row['value']);
    }

    public function testGetInvalidPinnedFallsThroughToXml(): void
    {
        $this->writePinned(self::APP_XML, $this->sampleRows(self::APP_XML), ['gov2options' => 'x']);

        $row = $this->opt(self::APP_XML)->get(['app' => self::APP_XML, 'nama' => 'Tahun']);

        $this->assertEquals('2020', $row['value']); // fall-through ke factory XML
    }

    public function testGetWithoutPinnedKeepsLegacyBehavior(): void
    {
        // Regresi BC: portal tanpa pinned → hasil identik perilaku lama
        $row = $this->opt(self::APP_XML)->get(['app' => self::APP_XML, 'nama' => 'Tahun']);

        $this->assertEquals('2020', $row['value']);
        $this->assertNull($this->opt('zzappnihil')->get(['app' => 'zzappnihil', 'nama' => 'Tahun']));
    }

    public function testGetAllPinnedReturnsLevelOneShapedRows(): void
    {
        $this->writePinned(self::APP_XML, $this->sampleRows(self::APP_XML));

        $rows = $this->opt(self::APP_XML)->getAll(self::APP_XML);

        $this->assertEquals(['Tahun dan Bulan'], array_column($rows, 'nama'));

        $keys = array_keys($rows[0]);
        sort($keys);
        $this->assertEquals(
            ['app', 'id', 'keterangan', 'nama', 'privilege', 'status', 'type', 'value'],
            $keys,
            'kolom getAll dari pinned harus sama dengan select jalur DB'
        );
    }

    public function testGetAllWithoutPinnedKeepsLegacyBehavior(): void
    {
        $rows = $this->opt(self::APP_XML)->getAll(self::APP_XML);

        $this->assertEquals(['Tahun dan Bulan', 'Warna'], array_column($rows, 'nama'));
        $this->assertEquals([], $this->opt('zzappnihil')->getAll('zzappnihil'));
    }

    // ---- sqlTierEffective + guard autoregistrasi MVC ----------------------

    public function testSqlTierEffectiveOnlyForMeekroWithoutPinned(): void
    {
        $this->assertTrue($this->opt(self::APP_DB)->sqlTierEffective());

        $this->writePinned(self::APP_DB, $this->sampleRows(self::APP_DB));
        $this->assertFalse($this->opt(self::APP_DB)->sqlTierEffective());

        $this->assertFalse($this->opt(self::APP_XML)->sqlTierEffective()); // driver statis
    }

    public function testMvcAutoregisterNoopWhenPinnedActive(): void
    {
        $this->writePinned(self::APP_DB, $this->sampleRows(self::APP_DB));
        $this->installSelfStub(self::APP_DB);

        $mvc = new MVC('unitMvc');

        $this->assertFalse($mvc->recorded);
        $this->assertEquals(0, $mvc->id);
        $this->assertEquals([], $GLOBALS['doc']->handled); // tak ada percobaan INSERT
    }

    public function testMvcAutoregisterNoopOnStaticTier(): void
    {
        $this->installSelfStub(self::APP_XML);

        $mvc = new MVC('unitMvc');

        $this->assertFalse($mvc->recorded);
        $this->assertEquals([], $GLOBALS['doc']->handled);
    }

    public function testMvcReadsExistingEntryFromPinned(): void
    {
        $rows = $this->sampleRows(self::APP_DB);
        $rows[] = ['id' => 3, 'parent_id' => 0, 'app' => self::APP_DB, 'nama' => 'MVC',
                   'type' => 'option', 'privilege' => 'admin', 'status' => 'on', 'value' => '',
                   'level' => 1, 'level_label' => 'cluster', 'keterangan' => ''];
        $rows[] = ['id' => 4, 'parent_id' => 3, 'app' => self::APP_DB, 'nama' => 'unitMvc',
                   'type' => 'checkbox', 'privilege' => 'admin', 'status' => 'on', 'value' => '1',
                   'level' => 2, 'level_label' => 'option', 'keterangan' => ''];
        $this->writePinned(self::APP_DB, $rows);
        $this->installSelfStub(self::APP_DB);

        $mvc = new MVC('unitMvc');

        $this->assertTrue($mvc->recorded);
        $this->assertEquals(4, $mvc->id);
        $this->assertEquals(3, $mvc->parent_id);
        $this->assertTrue($mvc->active);
    }

    private function installSelfStub(string $pageID): void
    {
        $self = new class {
            public $opt;
            public string $className = 'unitMvc';
        };
        $self->opt = $this->opt($pageID);
        $GLOBALS['self'] = $self;
    }
}
