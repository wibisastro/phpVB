<?php

namespace Gov2lib;

use Firebase\JWT\JWT;
use Gov2lib\Database\AgendbException;

/**
 * Agen sesi — pertukaran identitas kancil/gov3code → sesi portal, sebagai endpoint INTI untuk semua app
 * (generalisasi `agen.php` app sdi-staging yang dipakai MCP gov3sdi). Dipakai MCP generik `phpvb-run`
 * (rencana phpVB/rencana/phpvb-manifest-mcp-run.md §7) agar agen menjalankan cmd app ATAS NAMA pengguna
 * yang login di kancil, dengan role/konteks persis seperti ia membuka portal di browser.
 *
 *   GET  /{app}/agen[/ping]            — hidup? rahasia terpasang? (tanpa identitas)
 *   POST /{app}/agen {"cmd":"sesi"}    + header X-Phpvb-Agen-Secret, X-Gov3-User (email), X-Gov3-Session (audit)
 *                                       → {token (JWT Gov2Session), exp, csrf, member, superuser}
 *   POST /{app}/agen {"cmd":"siapa"}   + Cookie: Gov2Session=… → klaim sesi + konteks (tanpa rahasia; cookie = bukti)
 *
 * Token = JWT HS256 dengan $publickey server, klaim seperti hasil login SSO + authenticate() (account_id,
 * fullname, email, id, userRole, status, pageID) + penanda `agen`/`agen_session` untuk audit. Hanya member
 * yang SUDAH terdaftar (pernah login ke portal) yang bisa ditukar; tidak ada pembuatan member.
 *
 * Pengaman: mati tanpa rahasia per server (env PHPVB_AGEN_SECRET / core/config/agen.secret — TERPISAH dari
 * agendb.secret karena kewenangannya lebih besar: menerbitkan sesi); hash_equals; email wajib valid;
 * X-Gov3-Session wajib (hanya klien ber-identity:true); akun suspended/pending ditolak; TTL ≤ 24 jam;
 * audit satu baris JSON per permintaan ke error_log tanpa token. Semua penolakan fail-closed.
 */
class agen
{
    public const H_SECRET = 'HTTP_X_PHPVB_AGEN_SECRET';
    public const TTL_BAWAAN = 8 * 3600;
    public const TTL_MAKS = 24 * 3600;
    /** Kunci sesi yang dilaporkan `siapa` sebagai konteks (tanpa nilai lain di cookie). */
    public const KONTEKS = ['opd_id', 'opd', 'wilayah_id', 'wilayah', 'unit_id', 'unit', 'tahun', 'is_tagging_opd', 'data_tagging_opd'];

    private array $siapa = [];

    public function __construct()
    {
    }

    public function index($vars): array
    {
        return $this->ping($vars);
    }

    public function ping($vars): array
    {
        global $pageID;

        return ['ok' => true, 'app' => $pageID, 'agen' => 'agen', 'secret_configured' => self::secret() !== '',
            'server' => $_SERVER['SERVER_NAME'] ?? '', 'cmd' => ['ping', 'sesi', 'siapa']];
    }

    /** POST cmd=sesi — email → JWT Gov2Session member portal. */
    public function sesi($vars): array
    {
        global $self, $pageID, $publickey;

        $vars = is_array($vars) ? $vars : [];
        $t0 = microtime(true);
        try {
            if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
                throw new AgendbException('MethodNotAllowed', 'cmd sesi hanya lewat POST {"cmd":"sesi"}', 405);
            }
            $m = $this->gerbang($vars);
            $ttl = (int) ($vars['ttl'] ?? 0);
            if ($ttl <= 0 || $ttl > self::TTL_MAKS) {
                $ttl = $ttl > self::TTL_MAKS ? self::TTL_MAKS : self::TTL_BAWAAN;
            }
            $now = time();
            $claims = [
                'account_id' => (int) $m['account_id'],
                'fullname' => (string) ($m['fullname'] ?? ''),
                'email' => (string) $m['email'],
                'facebook' => null,
                'photourl' => null,
                'ssokey' => '',
                'id' => (int) $m['id'],
                'userRole' => (string) ($m['role'] ?? 'guest'),
                'status' => (string) ($m['status'] ?? 'active'),
                'pageID' => (string) $pageID,
                'iat' => $now,
                'exp' => $now + $ttl,
                'agen' => 'phpvb',
                'agen_session' => $this->siapa['session'],
            ];
            if (empty($publickey)) {
                throw new AgendbException('GALAT_SERVER', 'publickey server kosong — tidak bisa menerbitkan sesi', 500);
            }
            $token = JWT::encode($claims, (string) $publickey, 'HS256');
            // Token CSRF stateless framework (core/lib/csrf.php) — dipakai endpoint gov2option ber-guard
            $csrf = hash_hmac('sha256', 'gov2csrf.v2|' . $claims['account_id'], (string) $publickey);
            $this->audit('sesi', 'ok', (int) ((microtime(true) - $t0) * 1000), ['ttl' => $ttl]);

            return ['ok' => true, 'app' => $pageID, 'token' => $token, 'cookie' => 'Gov2Session', 'exp' => $claims['exp'], 'ttl' => $ttl, 'csrf' => $csrf,
                'member' => ['id' => (int) $m['id'], 'account_id' => (int) $m['account_id'], 'fullname' => $claims['fullname'], 'email' => $claims['email'],
                    'role' => $claims['userRole'], 'status' => $claims['status']],
                'superuser' => $this->siapa['superuser'] ?? null,
                'catatan' => 'kirim sebagai header Cookie: Gov2Session=<token> + Accept: application/json; serap Set-Cookie balasan (sesSave menerbitkan ulang); '
                    . '401 NotLogin = tukar ulang'];
        } catch (AgendbException $e) {
            $this->audit('sesi', $e->kode, (int) ((microtime(true) - $t0) * 1000));

            return $this->tolak($e->status, $e->kode, $e->getMessage(), $e->extra);
        } catch (\Throwable $e) {
            $this->audit('sesi', get_class($e), (int) ((microtime(true) - $t0) * 1000));

            return $this->tolak(500, 'GALAT_SERVER', get_class($e) . ': ' . $e->getMessage());
        }
    }

    /** POST/GET cmd=siapa — klaim sesi pemanggil (cookie Gov2Session) + konteks; tanpa rahasia. */
    public function siapa($vars): array
    {
        global $self, $pageID;

        $val = (isset($self->ses) && $self->ses instanceof gov2session) ? (array) $self->ses->val : [];
        if (empty($val['account_id'])) {
            return $this->tolak(401, 'NotLogin', 'tidak ada sesi portal — kirim Cookie: Gov2Session=<token hasil cmd sesi>');
        }
        $superuser = false;
        try {
            $superuser = $self->ses->checkSuperuser($pageID);
        } catch (\Throwable) {
            $superuser = false;
        }
        $konteks = [];
        foreach (self::KONTEKS as $k) {
            if (array_key_exists($k, $val)) {
                $konteks[$k] = $val[$k];
            }
        }

        return ['ok' => true, 'app' => $pageID,
            'member' => ['id' => (int) ($val['id'] ?? 0), 'account_id' => (int) $val['account_id'], 'fullname' => (string) ($val['fullname'] ?? ''),
                'email' => (string) ($val['email'] ?? ''), 'role' => (string) ($val['userRole'] ?? ''), 'status' => (string) ($val['status'] ?? '')],
            'superuser' => $superuser ?: null, 'peran_efektif' => $superuser ?: (string) ($val['userRole'] ?? ''),
            'konteks' => $konteks, 'exp' => $val['exp'] ?? null, 'agen' => $val['agen'] ?? null, 'pageID' => $val['pageID'] ?? null];
    }

    // ---------------------------------------------------------------- gerbang & bantu

    /** Rahasia bersama per server: env PHPVB_AGEN_SECRET, lalu berkas core/config/agen.secret (gitignored). */
    public static function secret(): string
    {
        $env = trim((string) getenv('PHPVB_AGEN_SECRET'));
        if ($env !== '') {
            return $env;
        }
        $file = __DIR__ . '/../config/agen.secret';
        if (is_readable($file)) {
            return trim((string) file_get_contents($file));
        }

        return '';
    }

    /** Rahasia → email → member → status; isi $this->siapa; kembalikan baris member. */
    private function gerbang(array $vars): array
    {
        global $self, $pageID;

        $secret = self::secret();
        if ($secret === '') {
            throw new AgendbException('AgenDisabled', 'PHPVB_AGEN_SECRET / core/config/agen.secret belum dikonfigurasi di server ini', 503);
        }
        $given = trim((string) ($_SERVER[self::H_SECRET] ?? ''));
        if ($given === '' || !hash_equals($secret, $given)) {
            throw new AgendbException('BadSecret', 'X-Phpvb-Agen-Secret tidak cocok', 403);
        }
        $email = strtolower(trim((string) ($_SERVER['HTTP_X_GOV3_USER'] ?? $vars['email'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new AgendbException('BLOCKED_NO_IDENTITY', 'butuh X-Gov3-User (email pemilik sesi kancil, entri MCP identity:true) atau argumen email', 422);
        }
        $sesi = mb_substr(trim((string) ($_SERVER['HTTP_X_GOV3_SESSION'] ?? $vars['session'] ?? '')), 0, 64);
        if ($sesi === '') {
            throw new AgendbException('SessionRequired', 'X-Gov3-Session (sesi kancil) wajib untuk audit — hanya klien ber-identity:true yang boleh menukar sesi', 422);
        }
        if (!$self instanceof agenHandler) {
            throw new AgendbException('GALAT_SERVER', 'handler agen tidak terpasang pada rute ini', 500);
        }
        $rows = $self->cariMember($email);
        if (count($rows) === 0) {
            throw new AgendbException('NoAccount', "tidak ada member portal dengan email {$email} — pengguna harus login sekali ke portal (SSO) agar terdaftar", 404);
        }
        if (count($rows) > 1) {
            throw new AgendbException('AmbiguousAccount', "email {$email} cocok dengan " . count($rows) . ' member — rapikan tabel member dulu', 409);
        }
        $m = $rows[0];
        $status = (string) ($m['status'] ?? 'active');
        if ($status === 'suspended') {
            throw new AgendbException('Suspended', 'akun portal terblokir, hubungi Admin', 403);
        }
        if ($status === 'pending') {
            throw new AgendbException('Pending', 'akun portal belum aktif', 403);
        }
        $superuser = false;
        if (isset($self->ses) && $self->ses instanceof gov2session) {
            $self->ses->val['account_id'] = $m['account_id'];
            try {
                $superuser = $self->ses->checkSuperuser($pageID);
            } catch (\Throwable) {
                $superuser = false;
            }
        }
        $this->siapa = ['email' => $email, 'id' => (int) $m['id'], 'account_id' => (int) $m['account_id'], 'role' => (string) ($m['role'] ?? ''),
            'superuser' => $superuser ?: null, 'session' => $sesi];

        return $m;
    }

    private function tolak(int $status, string $code, string $msg, array $extra = []): array
    {
        if (!headers_sent()) {
            http_response_code($status);
        }

        return ['ok' => false, 'error' => $code, 'message' => $msg, 'class' => 'is-danger', 'notification' => "{$code}: {$msg}"] + $extra;
    }

    private function audit(string $cmd, string $hasil, int $ms, array $tambahan = []): void
    {
        global $pageID;

        error_log(json_encode([
            'ev' => 'phpvb.agen', 'cmd' => $cmd, 'app' => $pageID, 'hasil' => $hasil, 'ms' => $ms,
            'email' => $this->siapa['email'] ?? strtolower(trim((string) ($_SERVER['HTTP_X_GOV3_USER'] ?? ''))),
            'uid' => $this->siapa['id'] ?? null, 'role' => $this->siapa['role'] ?? null, 'superuser' => $this->siapa['superuser'] ?? null,
            'session' => $this->siapa['session'] ?? mb_substr((string) ($_SERVER['HTTP_X_GOV3_SESSION'] ?? ''), 0, 64), 'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ] + $tambahan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
