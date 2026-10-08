<?php

namespace Gov2lib;

use Gov2lib\Database\AgendbException;
use Gov2lib\Database\ReadOnlySql as R;

/**
 * Model endpoint agen DB baca-saja (`/{app}/agendb`, controller Gov2lib\agendb).
 *
 * Membuka koneksi MySQL memakai entri `apps/{app}/xml/dsnSource.{STAGE}.xml` yang SAMA dengan
 * yang dipakai model app (bawaan: entri bernama SERVER_NAME, lihat core/config/index.php) —
 * tidak ada akun DB tambahan. Koneksi dibuka sendiri lewat mysqli (bukan `connectDB()`) agar
 * tidak mengubah kredensial statis \DB dan tidak menulis $doc->error; sesi langsung dikunci
 * `SET SESSION TRANSACTION READ ONLY` + batas waktu + `sql_select_limit`.
 *
 * Pencarian member (gerbang pengembang) memakai entri `master` seperti gov2session::memberRead.
 *
 * @since 2026-10-08 (v5.1.4)
 */
class agendbHandler extends checkExist
{
    public const MAKS_BARIS = 200;
    public const MAKS_TERBARU = 50;
    public const BATAS_WAKTU = 10;   // detik per kueri
    public const MAKS_TEKS = 300;    // karakter per nilai teks
    public const MAKS_CHAR = 60000;  // ukuran balasan

    /** Kolom yang wajar kosong pada baris baru — tidak dilaporkan sebagai "selalu kosong". */
    public const ABAIKAN_KOSONG = ['modify_at', 'modify_by', 'modified_at', 'modified_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'];

    private ?\mysqli $link = null;
    private string $dsnAktif = '';
    private string $dbAktif = '';

    public function __construct()
    {
        global $pageID;

        parent::__construct(); // dsnSource: dbTables.xml opsional, tidak dipakai di sini
        $this->templateDir = __DIR__ . "/../../apps/{$pageID}/view";
        $this->controller = __DIR__ . '/agendb.php';
    }

    // ---------------------------------------------------------------- DSN

    /**
     * Entri DSN app (ikut <share>) — kunci = nama entri.
     *
     * @return array<string, array{nama: string, driver: string, host: string, port: int, db: string, user: string, pass: string}>
     */
    public function entriDsn(): array
    {
        global $pageID;

        $path = __DIR__ . "/../../apps/{$pageID}/xml/dsnSource." . STAGE . '.xml';
        if (!file_exists($path)) {
            throw new AgendbException('NoDSNConfigFile', "apps/{$pageID}/xml/dsnSource." . STAGE . '.xml tidak ditemukan (J12)', 404);
        }
        $list = @simplexml_load_file($path);
        if (!is_object($list)) {
            throw new AgendbException('InvalidDSNConfigFile', "apps/{$pageID}/xml/dsnSource." . STAGE . '.xml tidak valid', 500);
        }
        if (!empty($list->share)) {
            $shared = __DIR__ . "/../../apps/{$list->share}/xml/dsnSource." . STAGE . '.xml';
            $sharedList = file_exists($shared) ? @simplexml_load_file($shared) : false;
            if (!is_object($sharedList)) {
                throw new AgendbException('DSNShareFileNotExist', "dsnSource share dari '{$list->share}' tidak ditemukan/valid", 500);
            }
            $list = $sharedList;
        }

        $out = [];
        foreach ($list->dsn as $d) {
            $nama = trim((string) $d->name);
            if ($nama === '') {
                continue;
            }
            $out[$nama] = [
                'nama' => $nama,
                'driver' => trim((string) $d->driver) ?: 'meekro',
                'host' => trim((string) $d->host),
                'port' => !empty($d->port) ? (int) trim((string) $d->port) : 3306,
                'db' => trim((string) $d->db),
                'user' => trim((string) $d->user),
                'pass' => trim((string) $d->pass),
            ];
        }

        return $out;
    }

    /** Nama entri DSN bawaan = yang dipakai model app pada domain ini. */
    public static function dsnBawaan(): string
    {
        global $config;

        return trim((string) ($config->domain->attr['dsn'] ?? 'master')) ?: 'master';
    }

    /** Daftar entri DSN tanpa rahasia — untuk agen memilih `dsn`. */
    public function daftarDsn(): array
    {
        $out = [];
        foreach ($this->entriDsn() as $e) {
            $out[] = ['nama' => $e['nama'], 'driver' => $e['driver'], 'db' => $e['db'], 'host' => $e['host'],
                'bawaan' => $e['nama'] === self::dsnBawaan()];
        }

        return $out;
    }

    /**
     * Buka (atau pakai ulang) koneksi baca-saja ke entri DSN.
     */
    public function buka(string $dsn = ''): \mysqli
    {
        $dsn = trim($dsn) ?: self::dsnBawaan();
        if ($this->link instanceof \mysqli && $this->dsnAktif === $dsn) {
            return $this->link;
        }
        $this->tutup();

        $entri = $this->entriDsn();
        if (!isset($entri[$dsn])) {
            throw new AgendbException('DSNEntryNotFound', "entri DSN '{$dsn}' tidak ada di dsnSource app", 404,
                ['tersedia' => array_keys($entri)]);
        }
        $e = $entri[$dsn];
        if ($e['driver'] !== 'meekro') {
            throw new AgendbException('DriverTidakDidukung', "entri '{$dsn}' berdriver {$e['driver']} — agendb hanya untuk MySQL "
                . '(driver meekro); tier supabase tidak punya SQL mentah', 501);
        }

        try {
            $m = new \mysqli();
            $m->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
            $ok = @$m->real_connect($e['host'], $e['user'], $e['pass'], $e['db'], $e['port']);
            if (!$ok) {
                throw new AgendbException('CannotConnectDSN', $this->bersihkanGalat((string) $m->connect_error, $e), 503);
            }
            $m->set_charset('utf8mb4');
            $m->query('SET SESSION TRANSACTION READ ONLY');
            // MySQL (milidetik) lalu MariaDB (detik) — salah satu akan diterima
            if (!@$m->query('SET SESSION max_execution_time = ' . (self::BATAS_WAKTU * 1000))) {
                @$m->query('SET SESSION max_statement_time = ' . self::BATAS_WAKTU);
            }
        } catch (\mysqli_sql_exception $x) {
            throw new AgendbException('CannotConnectDSN', $this->bersihkanGalat($x->getMessage(), $e), 503);
        }

        $this->link = $m;
        $this->dsnAktif = $dsn;
        $this->dbAktif = $e['db'];

        return $m;
    }

    public function dbAktif(): string
    {
        return $this->dbAktif;
    }

    public function tutup(): void
    {
        if ($this->link instanceof \mysqli) {
            try {
                $this->link->close();
            } catch (\Throwable) {
            }
        }
        $this->link = null;
        $this->dsnAktif = '';
        $this->dbAktif = '';
    }

    /** Pesan galat koneksi tanpa membocorkan kata sandi. */
    private function bersihkanGalat(string $pesan, array $e): string
    {
        return str_replace($e['pass'] !== '' ? $e['pass'] : "\0", '***', $pesan) . " (dsn {$e['nama']} → {$e['user']}@{$e['host']}/{$e['db']})";
    }

    // ---------------------------------------------------------------- eksekusi

    /**
     * Jalankan satu pernyataan pada koneksi aktif.
     *
     * @return array{kolom: array<int, string>, baris: array<int, array<int, mixed>>, terpotong: bool, ms: int}
     */
    public function jalankan(string $sql, int $maks = self::MAKS_BARIS, array $params = []): array
    {
        $m = $this->link ?? $this->buka();
        $maks = max(1, min($maks, 5000));
        $t0 = microtime(true);
        try {
            @$m->query('SET SESSION sql_select_limit = ' . ($maks + 1));
            if ($params) {
                $st = $m->prepare($sql);
                if (!$st) {
                    throw new AgendbException(...$this->kodeMysql($m->errno, $m->error));
                }
                $st->bind_param(str_repeat('s', count($params)), ...$params);
                $st->execute();
                $res = $st->get_result();
            } else {
                $res = $m->query($sql, MYSQLI_STORE_RESULT);
            }
            if ($res === false) {
                throw new AgendbException(...$this->kodeMysql($m->errno, $m->error));
            }
        } catch (\mysqli_sql_exception $x) {
            throw new AgendbException(...$this->kodeMysql((int) $x->getCode(), $x->getMessage()));
        }
        $kolom = [];
        $baris = [];
        if ($res instanceof \mysqli_result) {
            foreach ($res->fetch_fields() as $f) {
                $kolom[] = $f->name;
            }
            while (count($baris) <= $maks && ($r = $res->fetch_row()) !== null) {
                $baris[] = $r;
            }
            $res->free();
        }
        $terpotong = count($baris) > $maks;

        return ['kolom' => $kolom, 'baris' => array_slice($baris, 0, $maks), 'terpotong' => $terpotong,
            'ms' => (int) ((microtime(true) - $t0) * 1000)];
    }

    /** @return array{0: string, 1: string, 2: int, 3: array} argumen AgendbException */
    private function kodeMysql(int $errno, string $pesan): array
    {
        $peta = [
            1044 => ['AKSES_DITOLAK_MYSQL', 'user DSN app tidak punya hak itu', 403],
            1142 => ['AKSES_DITOLAK_MYSQL', 'user DSN app tidak punya hak itu', 403],
            1143 => ['AKSES_DITOLAK_MYSQL', 'user DSN app tidak punya hak itu', 403],
            1227 => ['AKSES_DITOLAK_MYSQL', 'butuh hak yang tidak dimiliki user DSN app', 403],
            1792 => ['BUKAN_BACA', 'sesi read-only menolak perubahan', 400],
            3024 => ['BATAS_WAKTU', 'kueri melewati batas ' . self::BATAS_WAKTU . ' detik — persempit WHERE / tambah LIMIT', 422],
            1969 => ['BATAS_WAKTU', 'kueri melewati batas ' . self::BATAS_WAKTU . ' detik — persempit WHERE / tambah LIMIT', 422],
            1064 => ['SQL_SALAH', 'sintaks SQL salah', 422],
            1054 => ['SQL_SALAH', 'kolom tidak dikenal — cek cmd struktur', 422],
            1146 => ['SQL_SALAH', 'tabel tidak ada — cek cmd tabel', 422],
            1049 => ['DB_TIDAK_DIKENAL', 'DB tidak ada — cek cmd dsn', 422],
        ];
        [$kode, $saran, $status] = $peta[$errno] ?? ["MYSQL_{$errno}", '', 422];

        return [$kode, $pesan, $status, ['kode_mysql' => $errno] + ($saran !== '' ? ['saran' => $saran] : [])];
    }

    // ---------------------------------------------------------------- member (gerbang pengembang)

    /**
     * Member portal dengan email ini (DSN master; SELECT saja). Bisa 0, 1, atau >1 baris.
     *
     * @return array<int, array<string, mixed>>
     */
    public function cariMember(string $email): array
    {
        $this->buka('master');
        $r = $this->jalankan('SELECT id, account_id, fullname, email, role, status FROM member WHERE LOWER(email)=? ORDER BY lastlogin_at DESC',
            10, [strtolower(trim($email))]);
        $out = [];
        foreach ($r['baris'] as $b) {
            $out[] = array_combine($r['kolom'], $b);
        }
        $this->tutup(); // koneksi master tidak dipakai untuk kueri app

        return $out;
    }

    // ---------------------------------------------------------------- informasi skema

    /** @return array<int, array{nama: string, jenis: string, perkiraan_baris: mixed, dibuat: mixed}> */
    public function daftarTabel(): array
    {
        $this->buka($this->dsnAktif);
        $r = $this->jalankan('SELECT TABLE_NAME, TABLE_TYPE, TABLE_ROWS, CREATE_TIME FROM information_schema.TABLES WHERE TABLE_SCHEMA=? ORDER BY TABLE_NAME',
            2000, [$this->dbAktif]);
        $out = [];
        foreach ($r['baris'] as [$nama, $jenis, $rows, $dibuat]) {
            $out[] = ['nama' => $nama, 'jenis' => str_contains((string) $jenis, 'VIEW') ? 'view' : 'tabel',
                'perkiraan_baris' => $rows === null ? null : (int) $rows, 'dibuat' => $dibuat];
        }

        return $out;
    }

    /**
     * Kolom semua tabel sekaligus (satu kueri information_schema) — kunci = nama tabel.
     *
     * @return array<string, array<int, array{nama: string, data_type: string, kunci: string, ekstra: string}>>
     */
    public function kolomSemuaTabel(): array
    {
        $r = $this->jalankan('SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_KEY, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? ORDER BY TABLE_NAME, ORDINAL_POSITION',
            5000, [$this->dbAktif]);
        $out = [];
        foreach ($r['baris'] as [$t, $k, $tipe, $kunci, $ekstra]) {
            $out[$t][] = ['nama' => $k, 'data_type' => strtolower((string) $tipe), 'kunci' => (string) $kunci, 'ekstra' => (string) $ekstra];
        }

        return $out;
    }

    /** @return array<int, array<string, mixed>> kolom satu tabel; TABEL_TIDAK_ADA bila kosong */
    public function kolomTabel(string $tabel): array
    {
        $r = $this->jalankan('SELECT COLUMN_NAME, COLUMN_TYPE, DATA_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT, EXTRA, COLUMN_COMMENT '
            . 'FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? ORDER BY ORDINAL_POSITION', 2000, [$this->dbAktif, $tabel]);
        if (!$r['baris']) {
            throw new AgendbException('TABEL_TIDAK_ADA', "tabel '{$tabel}' tidak ada di {$this->dbAktif}", 404,
                ['tabel_tersedia' => array_column($this->daftarTabel(), 'nama')]);
        }
        $out = [];
        foreach ($r['baris'] as [$a, $b, $c, $d, $e, $f, $g, $h]) {
            $out[] = ['nama' => $a, 'tipe' => $b, 'data_type' => strtolower((string) $c), 'null' => $d === 'YES', 'kunci' => (string) $e,
                'bawaan' => R::nilai($f, self::MAKS_TEKS), 'ekstra' => (string) $g, 'komentar' => (string) $h];
        }

        return $out;
    }

    public function struktur(string $tabel): array
    {
        $kol = $this->kolomTabel($tabel);
        $idx = $this->jalankan('SELECT INDEX_NAME, NON_UNIQUE, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) FROM information_schema.STATISTICS '
            . 'WHERE TABLE_SCHEMA=? AND TABLE_NAME=? GROUP BY INDEX_NAME, NON_UNIQUE ORDER BY INDEX_NAME', 500, [$this->dbAktif, $tabel]);
        $ddl = $this->jalankan('SHOW CREATE TABLE ' . R::q($this->dbAktif) . '.' . R::q($tabel), 1);
        $n = $this->jalankan('SELECT COUNT(*) FROM ' . R::q($this->dbAktif) . '.' . R::q($tabel), 1);
        $kw = R::kolomWaktu($kol);

        return [
            'tabel' => $tabel, 'jumlah_baris' => isset($n['baris'][0][0]) ? (int) $n['baris'][0][0] : null,
            'kolom' => $kol,
            'indeks' => array_map(fn($r) => ['nama' => $r[0], 'unik' => !(int) $r[1], 'kolom' => explode(',', (string) $r[2])], $idx['baris']),
            'kolom_waktu' => ['dibuat' => $kw['buat'], 'diubah' => $kw['ubah'], 'kunci' => $kw['pk']],
            'ddl' => $ddl['baris'][0][1] ?? null,
            'catatan_phpvb' => R::catatanStruktur($kol),
        ];
    }

    // ---------------------------------------------------------------- terbaru

    private function waktuServer(): ?string
    {
        $r = $this->jalankan('SELECT NOW()', 1);

        return isset($r['baris'][0][0]) ? (string) $r['baris'][0][0] : null;
    }

    /** Baris terbaru satu tabel + kolom selalu kosong + catatan J08. */
    private function ambil(string $tabel, array $kw, string $where, int $batas): array
    {
        $r = $this->jalankan(R::sqlTerbaru($tabel, $kw, $where, $batas), $batas);
        [$rows, $samar] = R::barisDict($r['kolom'], $r['baris'], self::MAKS_TEKS);
        $abaikan = array_merge(self::ABAIKAN_KOSONG, $kw['ubah'] ? [$kw['ubah']] : []);
        $catatan = [];
        if ($rows && array_key_exists('created_by', $rows[0])) {
            $tanpa = count(array_filter($rows, fn($d) => in_array($d['created_by'], [null, '', 0, '0'], true)));
            if ($tanpa) {
                $catatan[] = "created_by kosong di {$tanpa} dari " . count($rows) . ' baris — baris tidak lewat crudModel::doAdd '
                    . '(insert kustom di model?) atau sesi tanpa id pengguna (J08)';
            }
        }
        if (R::urut($kw) === '') {
            $catatan[] = "tanpa kolom waktu dan kunci primer tunggal — urutan 'terbaru' tidak terjamin";
        }

        return ['baris' => $rows, 'kolom_selalu_kosong' => R::kolomSelaluKosong($rows, $abaikan), 'disamarkan' => $samar, 'catatan' => $catatan];
    }

    public function terbaru(string $tabel, string $sejak, int $batas): array
    {
        $batas = max(1, min($batas ?: 10, self::MAKS_TERBARU));
        $waktuServer = $this->waktuServer();

        if ($tabel !== '') {
            $kw = R::kolomWaktu($this->kolomTabel($tabel));
            $where = '';
            $catatan = [];
            if ($sejak !== '') {
                if ($kw['buat'] || $kw['ubah']) {
                    $where = R::syaratSejak($kw, R::sejak($sejak));
                } else {
                    $catatan[] = "tabel tanpa kolom waktu (created_at/modify_at) — 'sejak' diabaikan, diurutkan menurut kunci primer";
                }
            }
            $jumlah = null;
            if ($where !== '') {
                $n = $this->jalankan('SELECT COUNT(*) FROM ' . R::q($tabel) . " WHERE {$where}", 1);
                $jumlah = (int) ($n['baris'][0][0] ?? 0);
            }
            $isi = $this->ambil($tabel, $kw, $where, $batas);
            $isi['catatan'] = array_merge($catatan, $isi['catatan']);
            if ($where !== '' && !$jumlah) {
                $isi['catatan'][] = "tidak ada baris dibuat/diubah sejak {$sejak} — aksi web tidak sampai ke tabel ini: cek respons endpoint "
                    . '(200 ≠ sukses, baca class/notification — J18), tabel di dbTables.xml, entri dsnSource app';
            }

            return R::pangkas(['tabel' => $tabel, 'waktu_server' => $waktuServer, 'sejak' => $sejak ?: null,
                'kolom_waktu' => ['dibuat' => $kw['buat'], 'diubah' => $kw['ubah'], 'kunci' => $kw['pk']],
                'urut' => R::urut($kw) ?: null, 'jumlah_cocok' => $jumlah] + $isi, 'baris', self::MAKS_CHAR);
        }

        // Sapu semua tabel dasar: hitung baris dibuat/diubah sejak <sejak> dalam satu UNION
        $sejak = $sejak ?: '1j';
        $ekspr = R::sejak($sejak);
        $dasar = array_column(array_filter($this->daftarTabel(), fn($t) => $t['jenis'] === 'tabel'), 'nama');
        $perTabel = array_intersect_key($this->kolomSemuaTabel(), array_flip($dasar));
        $bagian = [];
        $tanpa = [];
        $kwPer = [];
        ksort($perTabel);
        foreach ($perTabel as $t => $kol) {
            $kw = R::kolomWaktu($kol);
            if (!($kw['buat'] || $kw['ubah'])) {
                $tanpa[] = $t;
                continue;
            }
            $kwPer[$t] = $kw;
            $bagian[] = "SELECT '" . addslashes($t) . "' AS tabel, COUNT(*) AS n FROM " . R::q($t) . ' WHERE ' . R::syaratSejak($kw, $ekspr);
        }
        $berubah = [];
        if ($bagian) {
            $r = $this->jalankan(implode(' UNION ALL ', $bagian), count($bagian) + 1);
            foreach ($r['baris'] as [$t, $n]) {
                if ((int) $n) {
                    $berubah[$t] = (int) $n;
                }
            }
            arsort($berubah);
        }
        $per = max(1, min($batas, 5));
        $hasil = [];
        foreach (array_slice($berubah, 0, 10, true) as $t => $n) {
            $hasil[] = ['tabel' => $t, 'jumlah' => $n] + $this->ambil($t, $kwPer[$t], R::syaratSejak($kwPer[$t], $ekspr), $per);
        }
        $catatan = ['penghapusan tidak terlihat dari kolom waktu — cek id tertentu lewat cmd kueri atau tabel history_*/arsip'];
        if (count($berubah) > 10) {
            $catatan[] = count($berubah) . ' tabel berubah, baris hanya ditampilkan untuk 10 teratas';
        }
        if (!$berubah) {
            $catatan[] = "tidak ada tabel berkolom waktu yang berubah sejak {$sejak} — aksi web tidak sampai ke DB {$this->dbAktif}, "
                . 'atau app menulis lewat entri DSN lain (cek cmd dsn), atau tabelnya tanpa kolom waktu (lihat tanpa_kolom_waktu)';
        }

        return R::pangkas(['sejak' => $sejak, 'waktu_server' => $waktuServer, 'jumlah_tabel_diperiksa' => count($bagian),
            'berubah' => $hasil, 'tanpa_kolom_waktu' => $tanpa, 'catatan' => $catatan], 'berubah', self::MAKS_CHAR);
    }

    // ---------------------------------------------------------------- kueri bebas

    public function kueri(string $sql, int $batas): array
    {
        $bersih = R::periksa($sql);
        $batas = max(1, min($batas ?: 50, self::MAKS_BARIS));
        $r = $this->jalankan($bersih, $batas);
        [$rows, $samar] = R::barisDict($r['kolom'], $r['baris'], self::MAKS_TEKS);
        $hasil = ['kolom' => R::kolomUnik($r['kolom']), 'jumlah' => count($rows), 'terpotong' => $r['terpotong'], 'ms' => $r['ms'],
            'baris' => $rows, 'disamarkan' => $samar];
        if ($r['terpotong']) {
            $hasil['catatan'] = "hasil dipotong di {$batas} baris — persempit WHERE atau naikkan batas (maks " . self::MAKS_BARIS . ')';
        }

        return R::pangkas($hasil, 'baris', self::MAKS_CHAR);
    }
}
