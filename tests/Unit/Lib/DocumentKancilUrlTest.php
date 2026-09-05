<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\document;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for document::formatKancilUrl() — URL iframe slot sidepanel `kancil`.
 *
 * Priority: config platform/kancil → https://kancil.{SERVER_NAME} bila slug tenant ada di
 * kancilSlugs() (statis KANCIL_SLUGS, override platform/kancilSlugs) → "" (slug di luar
 * daftar, host tanpa titik). Pilot #10723: hanya kemenpanrb.
 */
class DocumentKancilUrlTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['config'] = new \SimpleXMLElement('<config/>');
        unset($_SERVER['SERVER_NAME']);
    }

    private function doc(): document
    {
        return new document();
    }

    public function testAutoFromServerNameUntukSlugPilot(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        $this->assertSame('https://kancil.kemenpanrb.gov3.id', $this->doc()->formatKancilUrl());
    }

    public function testServerNameLowercased(): void
    {
        $_SERVER['SERVER_NAME'] = 'KemenPANRB.Gov3.Id';
        $this->assertSame('https://kancil.kemenpanrb.gov3.id', $this->doc()->formatKancilUrl());
    }

    public function testEmptyUntukSlugDiLuarDaftar(): void
    {
        // Ikon Kancil tidak boleh tampil di portal selain pilot (#10723 brief 2026-09-05)
        $_SERVER['SERVER_NAME'] = 'acehbaratkab.gov3.id';
        $this->assertSame('', $this->doc()->formatKancilUrl());
        $this->assertSame(['kemenpanrb'], $this->doc()->kancilSlugs());
    }

    public function testConfigKancilSlugsMemperluasDaftar(): void
    {
        $GLOBALS['config'] = new \SimpleXMLElement(
            '<config><platform><kancilSlugs> Acehprov, kemenpanrb ,bad slug!</kancilSlugs></platform></config>'
        );
        $this->assertSame(['acehprov', 'kemenpanrb'], $this->doc()->kancilSlugs());
        $_SERVER['SERVER_NAME'] = 'acehprov.gov3.id';
        $this->assertSame('https://kancil.acehprov.gov3.id', $this->doc()->formatKancilUrl());
    }

    public function testConfigOverrideWinsTanpaCekDaftar(): void
    {
        $GLOBALS['config'] = new \SimpleXMLElement(
            '<config><platform><kancil>https://kancil-dev.gov3.id</kancil></platform></config>'
        );
        $_SERVER['SERVER_NAME'] = 'acehbaratkab.gov3.id';
        $this->assertSame('https://kancil-dev.gov3.id', $this->doc()->formatKancilUrl());
    }

    public function testKancilSessionSesuaiKontrakOpencode(): void
    {
        // #10722 journal 2026-09-04 10:33: b64url("kemenpanrb/sakipai-agen") tanpa padding
        $ks = $this->doc()->kancilSession('kemenpanrb');
        $this->assertSame('sakipai-agen', $ks['project']);
        $this->assertSame('/kemenpanrb/sakipai-agen', $ks['dir']);
        $this->assertSame('a2VtZW5wYW5yYi9zYWtpcGFpLWFnZW4', $ks['b64']);
        $this->assertStringNotContainsString('=', $ks['b64']);
        $this->assertDoesNotMatchRegularExpression('#[+/]#', $ks['b64']);
    }

    public function testKancilSessionSlugDariServerNameDanProjectDariConfig(): void
    {
        $GLOBALS['config'] = new \SimpleXMLElement(
            '<config><platform><kancilProject>pemdi</kancilProject></platform></config>'
        );
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        $ks = $this->doc()->kancilSession();
        $this->assertSame('kemenpanrb', $ks['slug']);
        $this->assertSame('/kemenpanrb/pemdi', $ks['dir']);
        $this->assertSame(rtrim(strtr(base64_encode('kemenpanrb/pemdi'), '+/', '-_'), '='), $ks['b64']);
    }

    public function testEmptyWhenHostHasNoDot(): void
    {
        $_SERVER['SERVER_NAME'] = 'localhost';
        $this->assertSame('', $this->doc()->formatKancilUrl());
    }

    public function testEmptyWhenServerNameMissing(): void
    {
        $this->assertSame('', $this->doc()->formatKancilUrl());
    }

    public function testEmptyWhenHostHasInvalidChars(): void
    {
        $_SERVER['SERVER_NAME'] = 'evil.gov3.id/"><script>';
        $this->assertSame('', $this->doc()->formatKancilUrl());
    }
}
