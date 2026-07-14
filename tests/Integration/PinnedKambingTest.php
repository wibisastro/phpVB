<?php

declare(strict_types=1);

namespace Tests\Integration;

use Gov2lib\gov2option;
use Gov2lib\pinnedStore;
use Gov2lib\webdavClient;
use PHPUnit\Framework\TestCase;

/**
 * Integration test kambing nyata (#6134 slice C): save-to-lower-tier →
 * file muncul di kambing → cache lokal terisi → resolver membacanya →
 * sync dari cache kosong menarik ulang dari kambing.
 *
 * Auto-SKIP tanpa kredensial (pola sama dengan integration MySQL):
 *   GOV2_KAMBING_URL=https://kambing.gov3.id/remote.php/dav/files/{akun}
 *   GOV2_KAMBING_USER={akun}  GOV2_KAMBING_PASS={app-password}
 */
class PinnedKambingTest extends TestCase
{
    private const APP = 'zzpinnedintegrasi';

    private string $dsn;
    private string $varDir;

    protected function setUp(): void
    {
        if (!getenv('GOV2_KAMBING_URL') || !getenv('GOV2_KAMBING_USER')) {
            $this->markTestSkipped('GOV2_KAMBING_URL/USER tidak di-set — butuh akun kambing dev');
        }

        // Nama folder dsn bisa dikontrol (mis. 'ayam' utk uji manual)
        $this->dsn = getenv('GOV2_KAMBING_TEST_DSN') ?: 'integrasi.uji.test';
        $this->varDir = sys_get_temp_dir() . '/gov2opt-kambing-' . getmypid();
        putenv('GOV2_VAR_DIR=' . $this->varDir);
    }

    protected function tearDown(): void
    {
        putenv('GOV2_VAR_DIR');

        if (isset($this->varDir) && is_dir($this->varDir)) {
            exec('rm -rf ' . escapeshellarg($this->varDir));
        }

        // Bersihkan folder uji di kambing (koleksi dsn saja, rekursif)
        if (isset($this->dsn)) {
            webdavClient::fromEnv()?->delete("portal-config/{$this->dsn}");
        }
    }

    public function testSaveRoundtripAndResync(): void
    {
        $rows = [
            ['id' => 3, 'parent_id' => 0, 'app' => self::APP, 'nama' => 'Integrasi',
             'type' => 'option', 'privilege' => 'admin', 'status' => 'on', 'value' => '',
             'level' => 1, 'level_label' => 'cluster', 'keterangan' => null],
            ['id' => 9, 'parent_id' => 3, 'app' => self::APP, 'nama' => 'Stempel',
             'type' => 'text', 'privilege' => 'admin', 'status' => 'on',
             'value' => 'kambing-' . getmypid(), 'level' => 2, 'level_label' => 'option', 'keterangan' => null],
        ];
        $envelope = pinnedStore::buildEnvelope($rows, self::APP, null, 'integration-test');
        $store = new pinnedStore();

        // 1. Save → kambing + cache lokal
        $result = $store->save($this->dsn, self::APP, $envelope);
        $this->assertEquals('ok', $result['remote'], 'PUT ke kambing harus sukses');
        $this->assertEquals('ok', $result['cache']);

        // 2. File benar-benar ada di kambing (GET langsung)
        $dav = webdavClient::fromEnv();
        $remote = $dav->get(pinnedStore::remotePath($this->dsn, self::APP));
        $this->assertEquals(200, $remote['status']);
        $this->assertStringContainsString('kambing-' . getmypid(), (string) $remote['body']);

        // 3. Resolver membaca cache lokal
        $file = gov2option::pinnedPath($this->dsn, self::APP);
        $parsed = gov2option::pinnedRowsFromFile($file, self::APP);
        $this->assertEquals('kambing-' . getmypid(), $parsed[1]['value']);

        // 4. Cache dihapus (disposable) → sync menarik ulang dari kambing
        unlink($file);
        @unlink($file . '.sync');
        $store->sync($this->dsn, self::APP);
        $this->assertFileExists($file, 'sync harus meregenerasi cache dari kambing');
    }
}
