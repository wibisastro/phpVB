<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Firebase\JWT\JWT;
use Gov2lib\csrf;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests csrf (#6134 slice C, revisi slice E) — token stateless HMAC
 * dari klaim account_id di JWT sesi + $publickey. Terikat identitas login,
 * BUKAN string cookie utuh: sesSave() me-re-issue JWT saat menyimpan state
 * (setRememberId drill-down) dan token tidak boleh basi karenanya.
 */
class CsrfTest extends TestCase
{
    private const KEY = 'kunci-uji-unit';

    private mixed $prevPublickey;
    private mixed $prevCookie;

    protected function setUp(): void
    {
        $this->prevPublickey = $GLOBALS['publickey'] ?? null;
        $this->prevCookie = $_COOKIE['Gov2Session'] ?? null;
        $GLOBALS['publickey'] = self::KEY;
        $_COOKIE['Gov2Session'] = self::jwt(['account_id' => 42, 'userRole' => 'webmaster']);
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

    private static function jwt(array $claims): string
    {
        return JWT::encode($claims, self::KEY, 'HS256');
    }

    public function testTokenDeterministicPerSession(): void
    {
        $token = csrf::token();

        $this->assertNotEquals('', $token);
        $this->assertEquals($token, csrf::token());
        $this->assertTrue(csrf::check($token));
    }

    public function testTokenSurvivesCookieReissueSameAccount(): void
    {
        // sesSave me-re-issue JWT dgn payload tambahan (mis. option_id hasil
        // setRememberId drill-down) — token TIDAK boleh basi (bug level-2 add)
        $token = csrf::token();
        $_COOKIE['Gov2Session'] = self::jwt(['account_id' => 42, 'userRole' => 'webmaster', 'option_id' => 7]);

        $this->assertEquals($token, csrf::token());
        $this->assertTrue(csrf::check($token));
    }

    public function testTokenChangesWithAccount(): void
    {
        $before = csrf::token();
        $_COOKIE['Gov2Session'] = self::jwt(['account_id' => 43]);

        $this->assertNotEquals($before, csrf::token());
        $this->assertFalse(csrf::check($before)); // token akun lama gugur
    }

    public function testCheckRejectsGarbage(): void
    {
        $this->assertFalse(csrf::check('salah'));
        $this->assertFalse(csrf::check(null));
        $this->assertFalse(csrf::check(['array' => 'bukan string']));
    }

    public function testNoSessionOrInvalidJwtMeansNoToken(): void
    {
        unset($_COOKIE['Gov2Session']);
        $this->assertEquals('', csrf::token());
        $this->assertFalse(csrf::check('')); // '' tidak boleh lolos sbg valid

        $_COOKIE['Gov2Session'] = 'bukan.jwt.valid';
        $this->assertEquals('', csrf::token());

        // JWT valid tapi tanpa account_id (sesi public) → tanpa token
        $_COOKIE['Gov2Session'] = self::jwt(['userRole' => 'public']);
        $this->assertEquals('', csrf::token());
    }
}
