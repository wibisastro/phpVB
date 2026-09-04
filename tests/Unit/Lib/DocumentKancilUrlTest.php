<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\document;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for document::formatKancilUrl() — URL iframe slot sidepanel `kancil`.
 *
 * Priority: config platform/kancil → https://kancil.{SERVER_NAME} → "" (host tanpa titik).
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

    public function testAutoFromServerName(): void
    {
        $_SERVER['SERVER_NAME'] = 'acehbaratkab.gov3.id';
        $this->assertSame('https://kancil.acehbaratkab.gov3.id', $this->doc()->formatKancilUrl());
    }

    public function testServerNameLowercased(): void
    {
        $_SERVER['SERVER_NAME'] = 'BKPM.Gov2.Web.Id';
        $this->assertSame('https://kancil.bkpm.gov2.web.id', $this->doc()->formatKancilUrl());
    }

    public function testConfigOverrideWins(): void
    {
        $GLOBALS['config'] = new \SimpleXMLElement(
            '<config><platform><kancil>https://kancil-dev.gov3.id</kancil></platform></config>'
        );
        $_SERVER['SERVER_NAME'] = 'acehbaratkab.gov3.id';
        $this->assertSame('https://kancil-dev.gov3.id', $this->doc()->formatKancilUrl());
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
