<?php

namespace Gov2lib;

/**
 * Enkripsi kolom credential (sodium secretbox) — #6134 slice C.
 *
 * Key dari env instance GOV2_CRED_KEY (base64 dari 32 byte acak, JANGAN
 * di-commit): `php -r "echo base64_encode(random_bytes(32));"`.
 * Fail-closed: tanpa key valid, encrypt() menolak (null + warning) —
 * credential tidak pernah tersimpan plaintext.
 *
 * @package Gov2lib
 */
class gov2crypto
{
    public const ENV_KEY = 'GOV2_CRED_KEY';

    /** Key biner dari env; null bila absen/format salah (dengan warning) */
    private static function key(): ?string
    {
        $b64 = getenv(self::ENV_KEY);

        if ($b64 === false || $b64 === '') {
            return null;
        }

        $key = base64_decode($b64, true);

        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            error_log('gov2crypto: ' . self::ENV_KEY . ' bukan base64 32 byte — enkripsi nonaktif');

            return null;
        }

        return $key;
    }

    /** Apakah key instance terpasang & valid */
    public static function ready(): bool
    {
        return self::key() !== null;
    }

    /**
     * Enkripsi plaintext → base64(nonce + cipher). Null bila key tidak
     * tersedia (fail-closed — pemanggil wajib menolak simpan).
     */
    public static function encrypt(string $plain): ?string
    {
        $key = self::key();

        if ($key === null) {
            error_log('gov2crypto: encrypt ditolak — ' . self::ENV_KEY . ' belum di-set di env instance');

            return null;
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, $key));
    }

    /** Dekripsi hasil encrypt(); null bila key absen / payload korup / tampered */
    public static function decrypt(string $sealed): ?string
    {
        $key = self::key();
        $raw = $key === null ? false : base64_decode($sealed, true);

        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }

        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, $key);

        return $plain === false ? null : $plain;
    }
}
