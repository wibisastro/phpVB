<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\context;
use Gov2lib\fileSource;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Gov2lib\fileSource (tier statis)
 *
 * Tests:
 * - resolve(): allowlist, filename pattern, base-dir containment, size cap
 * - multi-tenant lookup chain (tenant subfolder wins over generic)
 * - rantai konteks (Gov2lib\context): {slug}/{unit}/ → {slug}/ → root;
 *   konteks kosong → perilaku lama (tenant/ → root) — prinsip no-op
 * - candidateDirs(): urutan, dedup, tanpa segmen kosong, tolak traversal
 * - read(): parsed 'data' per format (csv/json/xml, raw formats null)
 * - parseCsv/parseJson/parseXml behavior on valid and invalid input
 * - tenant(): config override, subdomain autodetect, sanitization
 */
class FileSourceTest extends TestCase
{
    private string $appDir;

    protected function setUp(): void
    {
        $GLOBALS['config'] = new \SimpleXMLElement('<config/>');
        unset($_SERVER['SERVER_NAME'], $_COOKIE['Gov2Session']);
        context::reset();

        $this->appDir = sys_get_temp_dir() . '/filesource-test-' . uniqid();
        mkdir("{$this->appDir}/csv", 0777, true);
        mkdir("{$this->appDir}/json", 0777, true);
        mkdir("{$this->appDir}/md", 0777, true);
        mkdir("{$this->appDir}/md/bkpm", 0777, true);
        mkdir("{$this->appDir}/xml", 0777, true);

        file_put_contents("{$this->appDir}/csv/sample.csv", "nama,kota\nAndi,Bogor\nBudi,Depok\n");
        file_put_contents("{$this->appDir}/json/crud.json", '{"title": "CRUD", "items": [1, 2]}');
        file_put_contents("{$this->appDir}/md/index.md", "# Halo\n");
        file_put_contents("{$this->appDir}/md/bkpm/index.md", "# Halo BKPM\n");
        file_put_contents("{$this->appDir}/xml/menu.xml", '<menu><item>Beranda</item></menu>');
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->appDir));
        context::reset();
        unset($GLOBALS['config'], $_SERVER['SERVER_NAME'], $_COOKIE['Gov2Session']);
    }

    /**
     * Tulis file relatif terhadap appDir, membuat folder bila perlu.
     */
    private function put(string $rel, string $content): void
    {
        $path = "{$this->appDir}/{$rel}";
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $content);
    }

    private function source(): fileSource
    {
        return new fileSource($this->appDir);
    }

    public function testResolveReturnsContent(): void
    {
        $info = $this->source()->resolve('md', 'index');

        $this->assertNotNull($info);
        $this->assertEquals('index', $info['name']);
        $this->assertEquals('md', $info['format']);
        $this->assertEquals("# Halo\n", $info['content']);
    }

    public function testResolveRejectsUnknownFormat(): void
    {
        $src = $this->source();

        $this->assertNull($src->resolve('php', 'index'));
        $this->assertEquals('notfound', $src->lastError);
    }

    public function testResolveRejectsUnsafeName(): void
    {
        $src = $this->source();

        $this->assertNull($src->resolve('md', '../secret'));
        $this->assertNull($src->resolve('md', 'a/b'));
        $this->assertNull($src->resolve('md', ''));
        $this->assertEquals('notfound', $src->lastError);
    }

    public function testResolveMissingFileSetsNotFound(): void
    {
        $src = $this->source();

        $this->assertNull($src->resolve('md', 'tidakada'));
        $this->assertEquals('notfound', $src->lastError);
    }

    public function testResolveSizeCapFromConfig(): void
    {
        $GLOBALS['config'] = new \SimpleXMLElement(
            '<config><fileSource><maxFileSize><md>5</md></maxFileSize></fileSource></config>'
        );
        $src = $this->source();

        $this->assertNull($src->resolve('md', 'index'));
        $this->assertEquals('toolarge', $src->lastError);
    }

    public function testResolveSizeCapViewerFallbackKey(): void
    {
        $GLOBALS['config'] = new \SimpleXMLElement(
            '<config><viewer><maxFileSize><default>5</default></maxFileSize></viewer></config>'
        );
        $src = $this->source();

        $this->assertNull($src->resolve('md', 'index'));
        $this->assertEquals('toolarge', $src->lastError);
    }

    public function testTenantSubfolderWinsOverGeneric(): void
    {
        $_SERVER['SERVER_NAME'] = 'bkpm.gov2.web.id';

        $info = $this->source()->resolve('md', 'index');

        $this->assertNotNull($info);
        $this->assertEquals("# Halo BKPM\n", $info['content']);
    }

    public function testTenantFallsBackToGeneric(): void
    {
        $_SERVER['SERVER_NAME'] = 'bogor.gov2.web.id';

        $info = $this->source()->resolve('md', 'index');

        $this->assertNotNull($info);
        $this->assertEquals("# Halo\n", $info['content']);
    }

    // ---- rantai konteks (Gov2lib\context) ------------------------------------

    public function testKonteksUnitMenangAtasSlugDanGenerik(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        $this->put('md/kemenpanrb/index.md', "# Halo PANRB\n");
        $this->put('md/kemenkeu/index.md', "# Halo Kemenkeu\n");
        $this->put('md/kemenkeu/itjen/index.md', "# Halo Itjen Kemenkeu\n");
        context::load(['ctx_scope' => 'kl', 'ctx_slug' => 'kemenkeu', 'ctx_unit' => 'itjen', 'ctx_unit_id' => 5]);

        $info = $this->source()->resolve('md', 'index');

        $this->assertNotNull($info);
        $this->assertEquals("# Halo Itjen Kemenkeu\n", $info['content']);
        $this->assertStringEndsWith('/md/kemenkeu/itjen/index.md', $info['path']);
    }

    public function testKonteksUnitTanpaFileJatuhKeSlug(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        $this->put('md/kemenpanrb/index.md', "# Halo PANRB\n");
        $this->put('md/kemenkeu/index.md', "# Halo Kemenkeu\n");
        context::load(['ctx_scope' => 'kl', 'ctx_slug' => 'kemenkeu', 'ctx_unit' => 'itjen']);

        $info = $this->source()->resolve('md', 'index');

        $this->assertNotNull($info);
        $this->assertEquals("# Halo Kemenkeu\n", $info['content']);
    }

    public function testKonteksPortalLainTidakMemakaiFolderTenantSendiri(): void
    {
        // Konteks menunjuk kemenkeu (tanpa folder): harus jatuh ke generik,
        // BUKAN ke md/bkpm/ milik portal yang sedang dibuka.
        $_SERVER['SERVER_NAME'] = 'bkpm.gov2.web.id';
        context::load(['ctx_scope' => 'kl', 'ctx_slug' => 'kemenkeu', 'ctx_unit' => 'itjen']);

        $info = $this->source()->resolve('md', 'index');

        $this->assertNotNull($info);
        $this->assertEquals("# Halo\n", $info['content']);
    }

    public function testKonteksSlugSajaMemakaiFolderSlug(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        $this->put('json/jabarprov/crud.json', '{"title": "CRUD Jabar"}');
        context::load(['ctx_scope' => 'prov', 'ctx_slug' => 'jabarprov']);

        $info = $this->source()->read('json', 'crud');

        $this->assertNotNull($info);
        $this->assertEquals('CRUD Jabar', $info['data']['title']);
    }

    public function testDrillUnitPortalSendiriMemakaiFolderBersarang(): void
    {
        $_SERVER['SERVER_NAME'] = 'bkpm.gov2.web.id';
        $this->put('md/bkpm/itjen/index.md', "# Halo Itjen BKPM\n");
        context::load(['ctx_scope' => '', 'ctx_slug' => 'bkpm', 'ctx_unit' => 'itjen', 'ctx_unit_id' => 3]);

        $info = $this->source()->resolve('md', 'index');

        $this->assertNotNull($info);
        $this->assertEquals("# Halo Itjen BKPM\n", $info['content']);
    }

    public function testKonteksKosongTetapPerilakuLama(): void
    {
        // Folder unit ada, tetapi tanpa konteks tidak boleh dipakai (no-op).
        $_SERVER['SERVER_NAME'] = 'bkpm.gov2.web.id';
        $this->put('md/bkpm/itjen/index.md', "# Halo Itjen BKPM\n");
        context::load([]);

        $info = $this->source()->resolve('md', 'index');

        $this->assertNotNull($info);
        $this->assertEquals("# Halo BKPM\n", $info['content']);
    }

    public function testKonteksTakSahDiabaikanJatuhKeTenant(): void
    {
        $_SERVER['SERVER_NAME'] = 'bkpm.gov2.web.id';
        context::load(['ctx_scope' => 'kl', 'ctx_slug' => '../etc', 'ctx_unit' => 'passwd']);

        $info = $this->source()->resolve('md', 'index');

        $this->assertNotNull($info);
        $this->assertEquals("# Halo BKPM\n", $info['content']);
        $this->assertEquals(["{$this->appDir}/md/bkpm", "{$this->appDir}/md"], fileSource::candidateDirs("{$this->appDir}/md"));
    }

    // ---- candidateDirs() -----------------------------------------------------

    public function testCandidateDirsTanpaKonteksDanTanpaTenant(): void
    {
        $this->assertEquals(['/base'], fileSource::candidateDirs('/base'));
    }

    public function testCandidateDirsTanpaKonteksIdentikRantaiLama(): void
    {
        $_SERVER['SERVER_NAME'] = 'bkpm.gov2.web.id';

        $this->assertEquals(['/base/bkpm', '/base'], fileSource::candidateDirs('/base/'));
    }

    public function testCandidateDirsKonteksSlugSajaTanpaDuplikat(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        context::load(['ctx_scope' => 'kl', 'ctx_slug' => 'kemenkeu']);

        $this->assertEquals(['/base/kemenkeu', '/base'], fileSource::candidateDirs('/base'));
    }

    public function testCandidateDirsKonteksUnitBersarang(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        context::load(['ctx_scope' => 'kl', 'ctx_slug' => 'kemenkeu', 'ctx_unit' => 'itjen']);

        $this->assertEquals(
            ['/base/kemenkeu/itjen', '/base/kemenkeu', '/base'],
            fileSource::candidateDirs('/base')
        );
    }

    public function testCandidateDirsDrillUnitPortalSendiri(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        context::load(['ctx_scope' => '', 'ctx_slug' => 'kemenpanrb', 'ctx_unit' => 'itjen']);

        $this->assertEquals(
            ['/base/kemenpanrb/itjen', '/base/kemenpanrb', '/base'],
            fileSource::candidateDirs('/base')
        );
    }

    public function testCandidateDirsTenantDariConfigHurufBesarTetapDipakai(): void
    {
        // fileSource::tenant() case-insensitive: 'BKPM' dari attr tenant tetap jadi kandidat
        $GLOBALS['config'] = new \SimpleXMLElement('<config><domain><attr tenant="BKPM"/></domain></config>');

        $this->assertEquals(['/base/BKPM', '/base'], fileSource::candidateDirs('/base'));
    }

    public function testReadParsesCsv(): void
    {
        $info = $this->source()->read('csv', 'sample');

        $this->assertNotNull($info);
        $this->assertEquals(['id', 'nama', 'kota'], $info['data']['headers']);
        $this->assertCount(2, $info['data']['rows']);
        $this->assertEquals(['id' => 1, 'nama' => 'Andi', 'kota' => 'Bogor'], $info['data']['rows'][0]);
    }

    public function testReadParsesJson(): void
    {
        $info = $this->source()->read('json', 'crud');

        $this->assertNotNull($info);
        $this->assertEquals('CRUD', $info['data']['title']);
        $this->assertEquals([1, 2], $info['data']['items']);
    }

    public function testReadParsesXml(): void
    {
        $info = $this->source()->read('xml', 'menu');

        $this->assertNotNull($info);
        $this->assertInstanceOf(\SimpleXMLElement::class, $info['data']);
        $this->assertEquals('Beranda', (string) $info['data']->item);
    }

    public function testReadRawFormatHasNullData(): void
    {
        $info = $this->source()->read('md', 'index');

        $this->assertNotNull($info);
        $this->assertNull($info['data']);
    }

    public function testParseJsonInvalidReturnsNull(): void
    {
        $this->assertNull(fileSource::parseJson('{oops'));
    }

    public function testParseXmlInvalidReturnsNull(): void
    {
        $this->assertNull(fileSource::parseXml('<broken'));
    }

    public function testParseCsvKeepsExistingIdColumn(): void
    {
        $parsed = fileSource::parseCsv("id,nama\n7,Andi\n");

        $this->assertEquals(['id', 'nama'], $parsed['headers']);
        // Synthetic id is overwritten by the file's own id column
        $this->assertEquals(['id' => '7', 'nama' => 'Andi'], $parsed['rows'][0]);
    }

    public function testTenantFromConfigOverride(): void
    {
        // config/index.php:113-121 copies matched <domain> node attributes
        // into an <attr> child element on $config->domain
        $GLOBALS['config'] = new \SimpleXMLElement(
            '<config><domain><attr tenant="bkpm"/></domain></config>'
        );
        $_SERVER['SERVER_NAME'] = 'bogor.gov2.web.id';

        $this->assertEquals('bkpm', fileSource::tenant());
    }

    public function testTenantFromSubdomain(): void
    {
        $_SERVER['SERVER_NAME'] = 'depok.gov2.web.id';

        $this->assertEquals('depok', fileSource::tenant());
    }

    public function testTenantSanitized(): void
    {
        $_SERVER['SERVER_NAME'] = 'x!y.gov2.web.id';

        $this->assertEquals('', fileSource::tenant());
    }

    public function testTenantEmptyWithoutDots(): void
    {
        $_SERVER['SERVER_NAME'] = 'localhost';

        $this->assertEquals('', fileSource::tenant());
    }
}
