<?php

namespace Gov2lib;

use Gov2lib\Database\AgendbException;
use Gov2lib\Database\ReadOnlySqlException;

/**
 * Agen DB — endpoint BACA-SAJA atas DB app untuk PENGEMBANG, dipakai MCP gov3phpvb (tool `phpvb_db_*`,
 * skill `phpvb_cek_db`): memastikan hasil CRUD lewat web benar-benar masuk ke tabel yang benar.
 *
 *   GET  /{app}/agendb[/ping]                 — hidup? rahasia terpasang? (tanpa identitas)
 *   GET  /{app}/agendb/dsn|tabel              — + header di bawah
 *   POST /{app}/agendb  {"cmd": "dsn|tabel|struktur|terbaru|kueri", "dsn"?, "tabel"?, "sejak"?, "batas"?, "sql"?}
 *
 * Header: `Accept: application/json`, `X-Phpvb-Agendb-Secret` (rahasia bersama per server), `X-Gov3-User` (email
 * pemilik sesi kancil; entri MCP identity:true) [+ `X-Gov3-Session` untuk audit]. Email → member portal (DSN master)
 * yang harus **webmaster** (atau developer) atau tercantum di `apps/{app}/xml/superuser.xml` — pemilik app.
 *
 * Mati secara bawaan: tanpa rahasia (env PHPVB_AGENDB_SECRET atau berkas core/config/agendb.secret) → 503.
 * Kredensial DB = entri dsnSource app (bukan akun baru); sesi MySQL READ ONLY; penjaga SQL
 * (Gov2lib\Database\ReadOnlySql). Audit satu baris JSON per panggilan ke error_log — tanpa isi baris.
 * Rujukan: phpVB/rencana/gov3phpvb-mcp.md §10 F7, release_notes/v5.1.4.md.
 */
class agendb
{
    public const H_SECRET = 'HTTP_X_PHPVB_AGENDB_SECRET';
    public const PERAN_PENGEMBANG = ['webmaster', 'developer'];

    /** Member yang lolos gerbang pada request ini. */
    private array $siapa = [];

    public function __construct()
    {
    }

    // ---------------------------------------------------------------- cmd

    public function index($vars): array
    {
        return $this->ping($vars);
    }

    /** Cek hidup — boleh dipanggil tanpa identitas; tidak membocorkan nilai rahasia. */
    public function ping($vars): array
    {
        global $pageID;

        return ['ok' => true, 'app' => $pageID, 'agen' => 'agendb', 'secret_configured' => self::secret() !== '',
            'dsn_bawaan' => agendbHandler::dsnBawaan(), 'server' => $_SERVER['SERVER_NAME'] ?? '',
            'cmd' => ['dsn', 'tabel', 'struktur', 'terbaru', 'kueri']];
    }

    /** Entri DSN app (nama, driver, db, host; tanpa rahasia) — pilihan argumen `dsn`. */
    public function dsn($vars): array
    {
        return $this->jalan('dsn', $vars, fn(agendbHandler $m) => ['dsn' => $m->daftarDsn()]);
    }

    /** Daftar tabel + kolom waktu per tabel (dipakai terbaru). */
    public function tabel($vars): array
    {
        return $this->jalan('tabel', $vars, function (agendbHandler $m) {
            $kolom = $m->kolomSemuaTabel();
            $tabel = [];
            foreach ($m->daftarTabel() as $t) {
                $kw = Database\ReadOnlySql::kolomWaktu($kolom[$t['nama']] ?? []);
                $tabel[] = $t + ['kolom_waktu' => array_values(array_filter([$kw['buat'], $kw['ubah']]))];
            }

            return ['db' => $m->dbAktif(), 'jumlah_tabel' => count($tabel), 'tabel' => $tabel,
                'catatan' => 'perkiraan_baris = statistik InnoDB (bisa meleset); hitung pasti lewat cmd struktur atau kueri COUNT(*)'];
        });
    }

    /** Struktur satu tabel: kolom, indeks, DDL, jumlah baris, catatan konvensi phpVB. */
    public function struktur($vars): array
    {
        try {
            $tabel = $this->namaTabel(is_array($vars) ? $vars : []);
        } catch (AgendbException $e) {
            return $this->tolak($e->status, $e->kode, $e->getMessage());
        }

        return $this->jalan('struktur', $vars, fn(agendbHandler $m) => ['db' => $m->dbAktif()] + $m->struktur($tabel));
    }

    /** Tabel yang berubah sejak <sejak> (tanpa `tabel`) / baris terbaru satu tabel. */
    public function terbaru($vars): array
    {
        $tabel = trim((string) ($vars['tabel'] ?? ''));
        if ($tabel !== '' && !preg_match('/^[A-Za-z0-9_$]{1,64}$/', $tabel)) {
            return $this->tolak(422, 'TABEL_TIDAK_VALID', 'nama tabel hanya huruf/angka/_');
        }
        $sejak = trim((string) ($vars['sejak'] ?? ''));
        $batas = (int) ($vars['batas'] ?? 10);

        return $this->jalan('terbaru', $vars, fn(agendbHandler $m) => ['db' => $m->dbAktif()] + $m->terbaru($tabel, $sejak, $batas));
    }

    /** Satu pernyataan baca (SELECT/WITH/SHOW/DESCRIBE/EXPLAIN) yang ditulis agen. */
    public function kueri($vars): array
    {
        $sql = (string) ($vars['sql'] ?? '');
        $batas = (int) ($vars['batas'] ?? 50);

        return $this->jalan('kueri', $vars, fn(agendbHandler $m) => ['db' => $m->dbAktif()] + $m->kueri($sql, $batas));
    }

    // ---------------------------------------------------------------- gerbang & pembungkus

    /** Rahasia bersama per server: env PHPVB_AGENDB_SECRET, lalu berkas core/config/agendb.secret (gitignored). */
    public static function secret(): string
    {
        $env = trim((string) getenv('PHPVB_AGENDB_SECRET'));
        if ($env !== '') {
            return $env;
        }
        $file = __DIR__ . '/../config/agendb.secret';
        if (is_readable($file)) {
            return trim((string) file_get_contents($file));
        }

        return '';
    }

    private function namaTabel(array $vars): string
    {
        $tabel = trim((string) ($vars['tabel'] ?? ''));
        if (!preg_match('/^[A-Za-z0-9_$]{1,64}$/', $tabel)) {
            throw new AgendbException('TABEL_TIDAK_VALID', 'argumen tabel wajib: huruf/angka/_', 422);
        }

        return $tabel;
    }

    /**
     * Gerbang (rahasia → email → member → peran) lalu jalankan $fn pada handler yang sudah membuka DSN.
     */
    private function jalan(string $cmd, mixed $vars, callable $fn): array
    {
        global $self, $pageID;

        $vars = is_array($vars) ? $vars : [];
        $t0 = microtime(true);
        $dsn = trim((string) ($vars['dsn'] ?? ''));
        $jejak = ['dsn' => $dsn ?: agendbHandler::dsnBawaan(), 'tabel' => (string) ($vars['tabel'] ?? ''),
            'sql' => isset($vars['sql']) ? mb_substr((string) $vars['sql'], 0, 300) : null];
        try {
            $this->gerbang($vars);
            /** @var agendbHandler $self */
            $self->buka($dsn);
            $hasil = ['ok' => true, 'app' => $pageID, 'dsn' => $dsn ?: agendbHandler::dsnBawaan()] + $fn($self);
            $this->audit($cmd, 'ok', $jejak, (int) ((microtime(true) - $t0) * 1000));

            return $hasil;
        } catch (ReadOnlySqlException $e) {
            $this->audit($cmd, $e->kode, $jejak, (int) ((microtime(true) - $t0) * 1000));

            return $this->tolak(400, $e->kode, $e->pesan());
        } catch (AgendbException $e) {
            $this->audit($cmd, $e->kode, $jejak, (int) ((microtime(true) - $t0) * 1000));

            return $this->tolak($e->status, $e->kode, $e->getMessage(), $e->extra);
        } catch (\Throwable $e) {
            $this->audit($cmd, get_class($e), $jejak, (int) ((microtime(true) - $t0) * 1000));

            return $this->tolak(500, 'GALAT_SERVER', get_class($e) . ': ' . $e->getMessage());
        } finally {
            if ($self instanceof agendbHandler) {
                $self->tutup();
            }
        }
    }

    /** Lempar AgendbException bila ditolak; isi $this->siapa bila lolos. */
    private function gerbang(array $vars): void
    {
        global $self, $pageID;

        $secret = self::secret();
        if ($secret === '') {
            throw new AgendbException('AgenDbDisabled', 'PHPVB_AGENDB_SECRET / core/config/agendb.secret belum dikonfigurasi di server ini', 503);
        }
        $given = trim((string) ($_SERVER[self::H_SECRET] ?? ''));
        if ($given === '' || !hash_equals($secret, $given)) {
            throw new AgendbException('BadSecret', 'X-Phpvb-Agendb-Secret tidak cocok', 403);
        }

        $email = strtolower(trim((string) ($_SERVER['HTTP_X_GOV3_USER'] ?? $vars['email'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new AgendbException('BLOCKED_NO_IDENTITY', 'butuh X-Gov3-User (email pemilik sesi kancil, entri MCP identity:true) atau argumen email', 422);
        }

        if (!$self instanceof agendbHandler) {
            throw new AgendbException('GALAT_SERVER', 'handler agendb tidak terpasang pada rute ini', 500);
        }
        $rows = $self->cariMember($email);
        if (count($rows) === 0) {
            throw new AgendbException('NoAccount', "tidak ada member portal dengan email {$email} — login sekali ke portal (SSO) agar terdaftar", 404);
        }
        if (count($rows) > 1) {
            throw new AgendbException('AmbiguousAccount', "email {$email} cocok dengan " . count($rows) . ' member — rapikan tabel member dulu', 409);
        }
        $m = $rows[0];
        if (in_array((string) ($m['status'] ?? 'active'), ['suspended', 'pending'], true)) {
            throw new AgendbException('BLOCKED_STATUS', 'akun portal ' . $m['status'], 403);
        }

        // Pemilik app: peran webmaster/developer di tabel member, atau tercantum di apps/{app}/xml/superuser.xml
        $peran = (string) ($m['role'] ?? '');
        $superuser = false;
        if (isset($self->ses) && $self->ses instanceof gov2session) {
            $self->ses->val['account_id'] = $m['account_id'];
            $superuser = $self->ses->checkSuperuser($pageID);
        }
        if (!in_array($peran, self::PERAN_PENGEMBANG, true) && !$superuser) {
            throw new AgendbException('BLOCKED_BUKAN_PENGEMBANG', "{$email} berperan '{$peran}' dan tidak tercantum di superuser.xml app {$pageID} — "
                . 'endpoint ini hanya untuk pemilik/pengembang app (webmaster atau superuser.xml)', 403);
        }

        $this->siapa = ['email' => $email, 'id' => (int) $m['id'], 'account_id' => (int) $m['account_id'], 'role' => $peran,
            'superuser' => $superuser ?: null, 'session' => mb_substr((string) ($_SERVER['HTTP_X_GOV3_SESSION'] ?? $vars['session'] ?? ''), 0, 64)];
    }

    private function tolak(int $status, string $code, string $msg, array $extra = []): array
    {
        if (!headers_sent()) {
            http_response_code($status);
        }

        return ['ok' => false, 'error' => $code, 'message' => $msg, 'class' => 'is-danger', 'notification' => "{$code}: {$msg}"] + $extra;
    }

    private function audit(string $cmd, string $hasil, array $jejak, int $ms): void
    {
        global $pageID;

        error_log(json_encode([
            'ev' => 'phpvb.agendb', 'cmd' => $cmd, 'app' => $pageID, 'hasil' => $hasil, 'ms' => $ms,
            'email' => $this->siapa['email'] ?? strtolower(trim((string) ($_SERVER['HTTP_X_GOV3_USER'] ?? ''))),
            'uid' => $this->siapa['id'] ?? null, 'role' => $this->siapa['role'] ?? null, 'superuser' => $this->siapa['superuser'] ?? null,
            'session' => $this->siapa['session'] ?? null, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ] + $jejak, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
