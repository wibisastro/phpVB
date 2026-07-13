<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\gov2crypto;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests gov2crypto (#6134 slice C) — sodium secretbox utk kolom
 * credential; key dari env GOV2_CRED_KEY (base64 32 byte); fail-closed.
 */
class Gov2cryptoTest extends TestCase
{
    private string $errorLog;
    private string|false $prevErrorLog;

    protected function setUp(): void
    {
        $this->errorLog = sys_get_temp_dir() . '/gov2crypto-test-' . getmypid() . '.log';
        @unlink($this->errorLog);
        $this->prevErrorLog = ini_set('error_log', $this->errorLog);
        putenv(gov2crypto::ENV_KEY . '=' . base64_encode(str_repeat("\x42", SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));
    }

    protected function tearDown(): void
    {
        putenv(gov2crypto::ENV_KEY);
        @unlink($this->errorLog);

        if ($this->prevErrorLog !== false) {
            ini_set('error_log', $this->prevErrorLog);
        } else {
            ini_restore('error_log');
        }
    }

    public function testRoundtrip(): void
    {
        $sealed = gov2crypto::encrypt('token-rahasia-123');

        $this->assertNotNull($sealed);
        $this->assertStringNotContainsString('token-rahasia-123', $sealed);
        $this->assertEquals('token-rahasia-123', gov2crypto::decrypt($sealed));
    }

    public function testNonceMakesCiphertextUnique(): void
    {
        $this->assertNotEquals(gov2crypto::encrypt('sama'), gov2crypto::encrypt('sama'));
    }

    public function testTamperedPayloadReturnsNull(): void
    {
        $sealed = gov2crypto::encrypt('asli');
        $raw = base64_decode($sealed);
        $raw[strlen($raw) - 1] = $raw[strlen($raw) - 1] ^ "\x01";

        $this->assertNull(gov2crypto::decrypt(base64_encode($raw)));
        $this->assertNull(gov2crypto::decrypt('bukan-base64!!'));
    }

    public function testFailClosedWithoutKey(): void
    {
        putenv(gov2crypto::ENV_KEY);

        $this->assertFalse(gov2crypto::ready());
        $this->assertNull(gov2crypto::encrypt('apa pun')); // jangan pernah plaintext
        $this->assertNull(gov2crypto::decrypt('apa pun'));
    }

    public function testInvalidKeyLengthRejected(): void
    {
        putenv(gov2crypto::ENV_KEY . '=' . base64_encode('pendek'));

        $this->assertFalse(gov2crypto::ready());
        $this->assertNull(gov2crypto::encrypt('x'));
        $this->assertStringContainsString('bukan base64 32 byte', (string) file_get_contents($this->errorLog));
    }
}
