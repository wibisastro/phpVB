<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\fileSource;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Gov2lib\fileSource (tier statis)
 *
 * Tests:
 * - resolve(): allowlist, filename pattern, base-dir containment, size cap
 * - multi-tenant lookup chain (tenant subfolder wins over generic)
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
        unset($_SERVER['SERVER_NAME']);

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
        unset($GLOBALS['config'], $_SERVER['SERVER_NAME']);
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
