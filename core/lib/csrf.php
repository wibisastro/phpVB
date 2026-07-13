<?php

namespace Gov2lib;

/**
 * Token CSRF stateless — #6134 slice C (keamanan keputusan 9).
 *
 * Sesi phpVB = JWT di cookie Gov2Session (tanpa server-side store), maka
 * token diturunkan HMAC dari cookie sesi + $publickey: tidak butuh storage,
 * terikat sesi (cookie berganti → token lama gugur). Klien mengambil token
 * dari respons GET (mis. cmd getList) dan mengirimkannya kembali via header
 * `X-Gov2-Csrf` atau field `_csrf` pada setiap POST mutation.
 *
 * @package Gov2lib
 */
class csrf
{
    /** Token untuk sesi saat ini; '' bila belum login (tanpa cookie sesi) */
    public static function token(): string
    {
        global $publickey;

        $session = $_COOKIE['Gov2Session'] ?? '';

        if ($session === '' || empty($publickey)) {
            return '';
        }

        return hash_hmac('sha256', 'gov2csrf.v1|' . $session, (string) $publickey);
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
