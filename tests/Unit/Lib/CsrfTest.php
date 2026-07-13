<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\csrf;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests csrf (#6134 slice C) — token stateless HMAC(cookie sesi,
 * $publickey); terikat sesi, tanpa server-side store.
 */
class CsrfTest extends TestCase
{
    private mixed $prevPublickey;
    private mixed $prevCookie;

    protected function setUp(): void
    {
        $this->prevPublickey = $GLOBALS['publickey'] ?? null;
        $this->prevCookie = $_COOKIE['Gov2Session'] ?? null;
        $GLOBALS['publickey'] = 'kunci-uji-unit';
        $_COOKIE['Gov2Session'] = 'jwt.sesi.contoh';
    }

    protected function tearDown(): void
    {
        if ($this->prevPublickey === null) {
            unset($GLOBALS['publickey']);
        } else {
            $GLOBALS['publickey'] = $this->prevPublickey;
        }

        if ($this->prevCookie === null) {
            unset($_COOKIE['Gov2Session']);
        } else {
            $_COOKIE['Gov2Session'] = $this->prevCookie;
        }
    }

    public function testTokenDeterministicPerSession(): void
    {
        $token = csrf::token();

        $this->assertNotEquals('', $token);
        $this->assertEquals($token, csrf::token());
        $this->assertTrue(csrf::check($token));
    }

    public function testTokenChangesWithSession(): void
    {
        $before = csrf::token();
        $_COOKIE['Gov2Session'] = 'jwt.sesi.lain';

        $this->assertNotEquals($before, csrf::token());
        $this->assertFalse(csrf::check($before)); // token sesi lama gugur
    }

    public function testCheckRejectsGarbage(): void
    {
        $this->assertFalse(csrf::check('salah'));
        $this->assertFalse(csrf::check(null));
        $this->assertFalse(csrf::check(['array' => 'bukan string']));
    }

    public function testNoSessionMeansNoToken(): void
    {
        unset($_COOKIE['Gov2Session']);

        $this->assertEquals('', csrf::token());
        $this->assertFalse(csrf::check('')); // '' tidak boleh lolos sbg valid
    }
}
