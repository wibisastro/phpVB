<?php

namespace Gov2lib;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Token CSRF stateless — #6134 slice C (keamanan keputusan 9).
 *
 * Sesi phpVB = JWT di cookie Gov2Session (tanpa server-side store). Token
 * diturunkan HMAC dari klaim identitas STABIL di dalam JWT (account_id) +
 * $publickey — BUKAN dari string cookie utuh: sesSave() me-re-issue JWT
 * setiap menyimpan state sesi (mis. setRememberId saat drill-down tabel),
 * sehingga token berbasis cookie utuh akan basi di tengah umur halaman.
 * Token gugur saat logout (cookie hilang) / ganti akun (account_id beda).
 *
 * Klien: token di-render sekali ke halaman (cubeHead memasangnya sebagai
 * default header axios `X-Gov2-Csrf`) atau dikirim via field `_csrf`.
 *
 * @package Gov2lib
 */
class csrf
{
    /** Token untuk sesi login saat ini; '' bila belum login / JWT invalid */
    public static function token(): string
    {
        global $publickey;

        $session = $_COOKIE['Gov2Session'] ?? '';

        if ($session === '' || empty($publickey)) {
            return '';
        }

        try {
            $claims = (array) JWT::decode($session, new Key((string) $publickey, 'HS256'));
        } catch (\Throwable $e) {
            return '';
        }

        $accountId = (string) ($claims['account_id'] ?? '');

        if ($accountId === '') {
            return '';
        }

        return hash_hmac('sha256', 'gov2csrf.v2|' . $accountId, (string) $publickey);
    }

    /** Cocokkan token kiriman klien dengan token sesi (constant-time) */
    public static function check(mixed $token): bool
    {
        $expected = self::token();

        return $expected !== '' && is_string($token) && hash_equals($expected, $token);
    }

    /**
     * Gate endpoint mutation: token dari header X-Gov2-Csrf atau field _csrf.
     * Gagal → 403 + exit (jangan lanjut ke query).
     */
    public static function guard(): void
    {
        $token = $_SERVER['HTTP_X_GOV2_CSRF'] ?? $_POST['_csrf'] ?? null;

        if (!self::check($token)) {
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: application/json');
            echo json_encode(['notification' => 'Token CSRF tidak valid', 'class' => 'is-danger']);
            exit;
        }
    }
}
