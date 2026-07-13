<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\gov2option;
use PHPUnit\Framework\TestCase;

/**
 * Base fixture untuk test tier pinned JSON (#6134)
 *
 * Scaffolding bersama Gov2optionPinnedTest & Gov2optionValTest:
 * - GOV2_VAR_DIR menunjuk tmp dir sekali pakai; error_log dialihkan ke file
 *   supaya warning resolver bisa di-assert (dan tidak mengotori output)
 * - Fixture app runtime di apps/ (zzpinnedxml = tier statis + factory XML,
 *   zzpinneddb = driver meekro via dsnSource.test.xml yang hanya terbaca
 *   saat STAGE=test) — selalu dibersihkan di tearDown
 * - Stub $doc: envRead memasok dsn portal; exceptionHandler merekam panggilan
 *   supaya test bisa assert jalur error TIDAK tersentuh
 */
abstract class PinnedFixtureTestCase extends TestCase
{
    protected const DSN = 'unittest.portal.test';
    protected const APP_XML = 'zzpinnedxml';
    protected const APP_DB = 'zzpinneddb';

    protected string $varDir;
    protected string $appsDir;
    protected string $errorLog;
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

    protected static function rrmdir(string $dir): void
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
    protected function opt(string $pageID): gov2option
    {
        $GLOBALS['pageID'] = $pageID;

        return new gov2option();
    }

    /** Rows pinned berbentuk kolom options (id sintetis berurutan) */
    protected function sampleRows(string $app): array
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

    protected function writePinned(string $app, array $rows, array $overrides = []): void
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

    protected function warningsLogged(): string
    {
        return is_file($this->errorLog) ? (string) file_get_contents($this->errorLog) : '';
    }

    protected function installSelfStub(string $pageID): void
    {
        $self = new class {
            public $opt;
            public string $className = 'unitMvc';
        };
        $self->opt = $this->opt($pageID);
        $GLOBALS['self'] = $self;
    }
}
