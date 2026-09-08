<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\context;
use Gov2lib\document;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests untuk document::body('readMD') → resolveMD() mengikuti konteks
 * konten (Gov2lib\context) lewat fileSource::candidateDirs().
 *
 * resolveMD() menemukan folder md/ lewat Reflection pada class pemanggil
 * body(), jadi tes ini menulis controller fixture nyata ke direktori temp
 * (sekali per class) dan memanggil body('readMD') dari dalam method-nya.
 *
 * Kasus:
 * - konteks aktif: md/{slug}/{unit}/ menang atas md/{slug}/ menang atas md/
 * - konteks portal lain tidak pernah memakai md/{tenant}/ portal yang dibuka
 * - konteks kosong → perilaku lama: md/{tenant}/ lalu md/ (no-op)
 * - nama kosong → nama class pemanggil (ctrl.md)
 * - file tidak ada → mdMissing() menyebut SEMUA jalur yang dicoba
 */
class DocumentResolveMdContextTest extends TestCase
{
    private const FIXTURE_CLASS = 'Tests\\Fixture\\ResolveMdCtx\\ctrl';

    private static string $fixtureDir = '';

    public static function setUpBeforeClass(): void
    {
        self::$fixtureDir = sys_get_temp_dir() . '/document-resolvemd-test-' . uniqid();
        mkdir(self::$fixtureDir . '/md', 0777, true);

        // Controller fixture: body('readMD') dipanggil dari method class ini sehingga
        // debug_backtrace di document::body() melihat class ini sebagai caller dan
        // resolveMD() mencari <dir fixture>/md/{name}.md.
        file_put_contents(self::$fixtureDir . '/ctrl.php', <<<'PHP'
<?php

namespace Tests\Fixture\ResolveMdCtx;

final class ctrl
{
    public function __construct(private \Gov2lib\document $doc)
    {
    }

    public function readMD(string $name = ''): string
    {
        $this->doc->body('readMD', $name);

        return (string) $this->doc->body['readMD'];
    }
}
PHP);

        require_once self::$fixtureDir . '/ctrl.php';
    }

    public static function tearDownAfterClass(): void
    {
        exec('rm -rf ' . escapeshellarg(self::$fixtureDir));
    }

    protected function setUp(): void
    {
        $GLOBALS['config'] = new \SimpleXMLElement('<config/>');
        unset($_SERVER['SERVER_NAME'], $_COOKIE['Gov2Session']);
        context::reset();

        // Bersihkan md/ dari tes sebelumnya (fixture dir dipakai bersama satu class)
        exec('rm -rf ' . escapeshellarg(self::$fixtureDir . '/md'));
        mkdir(self::$fixtureDir . '/md', 0777, true);
    }

    protected function tearDown(): void
    {
        context::reset();
        unset($GLOBALS['config'], $_SERVER['SERVER_NAME'], $_COOKIE['Gov2Session']);
    }

    private function put(string $rel, string $content): void
    {
        $path = self::$fixtureDir . '/md/' . $rel;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $content);
    }

    private function readMD(string $name = ''): string
    {
        $class = self::FIXTURE_CLASS;

        return (new $class(new document()))->readMD($name);
    }

    public function testKonteksUnitMenangAtasSlugDanGenerik(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        $this->put('index.md', "# Generik\n");
        $this->put('kemenpanrb/index.md', "# Halo PANRB\n");
        $this->put('kemenkeu/index.md', "# Halo Kemenkeu\n");
        $this->put('kemenkeu/itjen/index.md', "# Halo Itjen Kemenkeu\n");
        context::load(['ctx_scope' => 'kl', 'ctx_slug' => 'kemenkeu', 'ctx_unit' => 'itjen', 'ctx_unit_id' => 5]);

        $this->assertStringContainsString('Halo Itjen Kemenkeu', $this->readMD('index'));
    }

    public function testKonteksUnitTanpaFileJatuhKeSlug(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        $this->put('index.md', "# Generik\n");
        $this->put('kemenpanrb/index.md', "# Halo PANRB\n");
        $this->put('kemenkeu/index.md', "# Halo Kemenkeu\n");
        context::load(['ctx_scope' => 'kl', 'ctx_slug' => 'kemenkeu', 'ctx_unit' => 'itjen']);

        $html = $this->readMD('index');

        $this->assertStringContainsString('Halo Kemenkeu', $html);
        $this->assertStringNotContainsString('PANRB', $html);
    }

    public function testKonteksPortalLainJatuhKeGenerikBukanTenantSendiri(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        $this->put('index.md', "# Generik\n");
        $this->put('kemenpanrb/index.md', "# Halo PANRB\n");
        context::load(['ctx_scope' => 'kl', 'ctx_slug' => 'kemenkeu', 'ctx_unit' => 'itjen']);

        $html = $this->readMD('index');

        $this->assertStringContainsString('Generik', $html);
        $this->assertStringNotContainsString('PANRB', $html);
    }

    public function testKonteksKosongTetapPerilakuLamaTenantLaluGenerik(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        $this->put('index.md', "# Generik\n");
        $this->put('kemenpanrb/index.md', "# Halo PANRB\n");
        $this->put('kemenpanrb/itjen/index.md', "# Halo Itjen PANRB\n");

        // Folder unit ada, tetapi tanpa konteks tidak boleh dipakai (no-op)
        $html = $this->readMD('index');

        $this->assertStringContainsString('Halo PANRB', $html);
        $this->assertStringNotContainsString('Itjen', $html);

        // Tanpa folder tenant → generik (persis rantai lama)
        exec('rm -rf ' . escapeshellarg(self::$fixtureDir . '/md/kemenpanrb'));
        $this->assertStringContainsString('Generik', $this->readMD('index'));
    }

    public function testDrillUnitPortalSendiriMemakaiFolderBersarang(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        $this->put('kemenpanrb/index.md', "# Halo PANRB\n");
        $this->put('kemenpanrb/itjen/index.md', "# Halo Itjen PANRB\n");
        context::load(['ctx_scope' => '', 'ctx_slug' => 'kemenpanrb', 'ctx_unit' => 'itjen', 'ctx_unit_id' => 3]);

        $this->assertStringContainsString('Halo Itjen PANRB', $this->readMD('index'));
    }

    public function testNamaKosongMemakaiNamaClassPemanggil(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        $this->put('kemenkeu/ctrl.md', "# Ctrl Kemenkeu\n");
        context::load(['ctx_scope' => 'kl', 'ctx_slug' => 'kemenkeu']);

        $this->assertStringContainsString('Ctrl Kemenkeu', $this->readMD());
    }

    public function testMdMissingMenyebutSemuaJalurYangDicoba(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        context::load(['ctx_scope' => 'kl', 'ctx_slug' => 'kemenkeu', 'ctx_unit' => 'itjen']);

        $html = $this->readMD('index');

        $this->assertStringContainsString(
            'md/kemenkeu/itjen/index.md atau md/kemenkeu/index.md atau md/index.md',
            $html
        );
    }

    public function testMdMissingTanpaKonteksSepertiSebelumnya(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';

        $this->assertStringContainsString(
            'md/kemenpanrb/index.md atau md/index.md',
            $this->readMD('index')
        );

        unset($_SERVER['SERVER_NAME']);
        $html = $this->readMD('index');
        $this->assertStringContainsString('md/index.md', $html);
        $this->assertStringNotContainsString(' atau ', $html);
    }
}
