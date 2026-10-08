<?php

namespace Gov2lib\Database;

/**
 * Logika murni endpoint agen DB baca-saja (`/{app}/agendb`, lihat Gov2lib\agendb):
 * penjaga teks SQL, kolom waktu/kunci, format nilai, penyamaran kolom rahasia,
 * dan penyusun SQL bantu. Tidak menyentuh koneksi — diuji di tests/Unit/Lib/ReadOnlySqlTest.php.
 *
 * Penjaga teks SQL adalah lapisan KETIGA, bukan dinding: dinding sebenarnya adalah
 * `SET SESSION TRANSACTION READ ONLY` (MySQL menolak DML, ER 1792) dan hak user DSN
 * app yang tidak punya DDL. Penjaga ini menolak lebih awal dengan pesan yang jelas
 * dan menutup jalur yang masih "baca" bagi MySQL tetapi berbahaya (INTO OUTFILE,
 * FOR UPDATE, SLEEP, komentar eksekutabel, banyak pernyataan).
 *
 * @since 2026-10-08 (v5.1.4) — permintaan Wibi: cek hasil CRUD web lewat API ber-DSN app, tanpa akun DB baru.
 */
final class ReadOnlySql
{
    public const BACA = ['select', 'with', 'show', 'describe', 'desc', 'explain'];

    /** Kolom yang nilainya disamarkan di balasan (kesopanan terhadap konteks model, bukan dinding). */
    public const POLA_RAHASIA = '/pass(word|wd)?|pwd|secret|rahasia|token|ssokey|api_?key|private_?key|publickey|salt/i';

    public const KOLOM_UBAH = ['modify_at', 'modified_at', 'updated_at', 'update_at', 'modify_date', 'modified_date', 'updated_date',
        'last_update', 'last_modified', 'tgl_update', 'tanggal_update', 'modified', 'updated'];
    public const KOLOM_BUAT = ['created_at', 'create_at', 'created_date', 'create_date', 'inserted_at', 'date_created', 'tgl_input',
        'tanggal_input', 'tgl_buat', 'created'];
    public const TIPE_WAKTU = ['datetime', 'timestamp', 'date'];

    private const TERLARANG = [
        '/\binto\s+(outfile|dumpfile)\b/' => 'INTO OUTFILE/DUMPFILE menulis berkas di server',
        '/\binto\s+@/' => 'SELECT … INTO @variabel',
        '/\bfor\s+(update|share)\b/' => 'FOR UPDATE/SHARE mengunci baris',
        '/\block\s+in\s+share\s+mode\b/' => 'LOCK IN SHARE MODE mengunci baris',
        '/\b(sleep|benchmark|load_file|get_lock|release_lock|release_all_locks)\s*\(/' => 'fungsi penahan/pembaca berkas',
    ];

    /** WITH … DELETE (MySQL 8), EXPLAIN/DESCRIBE <dml>: pernyataan "baca" yang membungkus perubahan. */
    private const UBAH = '/\b(insert|update|delete|replace|create|drop|alter|truncate|rename|grant|revoke|call|load)\b/';

    private const SATUAN = ['m' => 'MINUTE', 'mnt' => 'MINUTE', 'menit' => 'MINUTE', 'min' => 'MINUTE',
        'j' => 'HOUR', 'jam' => 'HOUR', 'h' => 'HOUR', 'hour' => 'HOUR',
        'd' => 'DAY', 'hari' => 'DAY', 'day' => 'DAY'];

    // ---------------------------------------------------------------- penjaga SQL

    /**
     * Kembalikan SQL siap eksekusi (titik koma penutup dibuang) atau lempar ReadOnlySqlException.
     */
    public static function periksa(string $sql): string
    {
        $s = trim($sql);
        if ($s === '') {
            throw new ReadOnlySqlException('SQL_KOSONG', 'sql kosong');
        }
        if (strlen($s) > 20000) {
            throw new ReadOnlySqlException('SQL_TERLALU_PANJANG', 'maks 20.000 karakter');
        }

        $k = trim(self::kerangka($s));
        $k = rtrim(rtrim($k, ';'));
        if (str_contains($k, ';')) {
            throw new ReadOnlySqlException('BANYAK_PERNYATAAN', 'hanya satu pernyataan per panggilan');
        }

        $kata = preg_match('/^[\s(]*([A-Za-z]+)/', $k, $m) ? strtolower($m[1]) : '';
        if (!in_array($kata, self::BACA, true)) {
            throw new ReadOnlySqlException('BUKAN_BACA', "'" . ($kata !== '' ? strtoupper($kata) : '?') . "' ditolak — endpoint ini baca-saja "
                . '(SELECT/WITH/SHOW/DESCRIBE/EXPLAIN). Perubahan data/skema lewat berkas sql/ di repo app + jalur deploy.');
        }

        $kl = strtolower($k);
        foreach (self::TERLARANG as $pola => $alasan) {
            if (preg_match($pola, $kl)) {
                throw new ReadOnlySqlException('SQL_TERLARANG', $alasan);
            }
        }
        if (in_array($kata, ['with', 'explain', 'describe', 'desc'], true) && preg_match(self::UBAH, $kl, $m2)) {
            throw new ReadOnlySqlException('BUKAN_BACA', strtoupper($kata) . ' membungkus ' . strtoupper($m2[1])
                . ' — ditolak. Bila itu nama kolom/tabel, kutip dengan backtick.');
        }

        return rtrim(rtrim($s), ';');
    }

    /**
     * Teks SQL dengan literal string → '' dan identifier ber-backtick → x, komentar dibuang
     * (aturan MySQL: `-- ` wajib diikuti spasi). Hanya untuk pemeriksaan kata kunci; yang
     * dieksekusi tetap teks asli.
     */
    public static function kerangka(string $sql): string
    {
        $out = '';
        $i = 0;
        $n = strlen($sql);
        while ($i < $n) {
            $c = $sql[$i];
            if ($c === "'" || $c === '"' || $c === '`') {
                $j = $i + 1;
                while ($j < $n) {
                    if ($sql[$j] === '\\' && $c !== '`') {
                        $j += 2;
                        continue;
                    }
                    if ($sql[$j] === $c) {
                        if ($j + 1 < $n && $sql[$j + 1] === $c) { // kutip ganda = kutip literal
                            $j += 2;
                            continue;
                        }
                        break;
                    }
                    $j++;
                }
                if ($j >= $n) {
                    throw new ReadOnlySqlException('SQL_TIDAK_LENGKAP', "kutip {$c} tidak ditutup");
                }
                $out .= $c === '`' ? 'x' : "''";
                $i = $j + 1;
                continue;
            }
            if (substr($sql, $i, 2) === '/*') {
                if (substr($sql, $i, 3) === '/*!' || substr($sql, $i, 4) === '/*M!') {
                    throw new ReadOnlySqlException('KOMENTAR_EKSEKUTABEL', 'komentar /*! … */ dieksekusi MySQL — tidak diterima');
                }
                $j = strpos($sql, '*/', $i + 2);
                if ($j === false) {
                    throw new ReadOnlySqlException('SQL_TIDAK_LENGKAP', 'komentar /* tidak ditutup');
                }
                $out .= ' ';
                $i = $j + 2;
                continue;
            }
            if ($c === '#' || (substr($sql, $i, 2) === '--' && ($i + 2 >= $n || strpbrk($sql[$i + 2], " \t\r\n") !== false))) {
                $j = strpos($sql, "\n", $i);
                $i = $j === false ? $n : $j;
                $out .= ' ';
                continue;
            }
            $out .= $c;
            $i++;
        }

        return $out;
    }

    // ---------------------------------------------------------------- waktu & kunci

    /**
     * '15m' / '2j' / '2h' (jam) / '1d' (hari) → `NOW() - INTERVAL …` (jam server DB);
     * 'YYYY-MM-DD HH:MM[:SS]' → literal ter-format (aman: ditulis ulang oleh kita).
     */
    public static function sejak(string $sejak): string
    {
        $s = strtolower(trim($sejak));
        if (preg_match('/^(\d{1,5})\s*([a-z]+)$/', $s, $m) && isset(self::SATUAN[$m[2]])) {
            return 'NOW() - INTERVAL ' . (int) $m[1] . ' ' . self::SATUAN[$m[2]];
        }
        // '!' = bidang yang tidak ada di format di-nol-kan (tanpa itu jam diisi waktu sekarang)
        $t = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', str_replace('T', ' ', trim($sejak)))
            ?: \DateTimeImmutable::createFromFormat('!Y-m-d H:i', str_replace('T', ' ', trim($sejak)))
            ?: \DateTimeImmutable::createFromFormat('!Y-m-d', trim($sejak));
        if ($t === false) {
            throw new ReadOnlySqlException('SEJAK_TIDAK_DIKENAL', "sejak = durasi relatif (15m, 2j/2h = jam, 1d = hari) atau "
                . "'YYYY-MM-DD HH:MM[:SS]' menurut jam server DB");
        }

        return "'" . $t->format('Y-m-d H:i:s') . "'";
    }

    /**
     * Kolom waktu & kunci dari daftar kolom information_schema.
     *
     * @param array<int, array{nama: string, data_type: string, kunci: string, ekstra: string}> $kolom
     * @return array{buat: ?string, ubah: ?string, pk: ?string}
     */
    public static function kolomWaktu(array $kolom): array
    {
        $waktu = [];
        foreach ($kolom as $k) {
            if (in_array(strtolower((string) $k['data_type']), self::TIPE_WAKTU, true)) {
                $waktu[strtolower($k['nama'])] = $k['nama'];
            }
        }
        $buat = $ubah = null;
        foreach (self::KOLOM_BUAT as $n) {
            if (isset($waktu[$n])) { $buat = $waktu[$n]; break; }
        }
        foreach (self::KOLOM_UBAH as $n) {
            if (isset($waktu[$n])) { $ubah = $waktu[$n]; break; }
        }
        $pri = array_values(array_filter($kolom, fn($k) => ($k['kunci'] ?? '') === 'PRI'));
        $pk = null;
        foreach ($pri as $k) {
            if (str_contains((string) ($k['ekstra'] ?? ''), 'auto_increment')) { $pk = $k['nama']; break; }
        }
        if ($pk === null && count($pri) === 1) {
            $pk = $pri[0]['nama'];
        }

        return ['buat' => $buat, 'ubah' => $ubah, 'pk' => $pk];
    }

    /** @param array{buat: ?string, ubah: ?string, pk: ?string} $kw */
    public static function urut(array $kw): string
    {
        $bag = [];
        if ($kw['buat'] && $kw['ubah']) {
            $bag[] = 'COALESCE(' . self::q($kw['ubah']) . ', ' . self::q($kw['buat']) . ') DESC';
        } elseif ($kw['buat'] || $kw['ubah']) {
            $bag[] = self::q($kw['buat'] ?: $kw['ubah']) . ' DESC';
        }
        if ($kw['pk']) {
            $bag[] = self::q($kw['pk']) . ' DESC';
        }

        return implode(', ', $bag);
    }

    /** Syarat WHERE "dibuat atau diubah sejak <ekspr>" — '' bila tabel tanpa kolom waktu. */
    public static function syaratSejak(array $kw, string $ekspr): string
    {
        $bag = [];
        foreach ([$kw['buat'], $kw['ubah']] as $c) {
            if ($c) {
                $bag[] = self::q($c) . ' >= ' . $ekspr;
            }
        }

        return implode(' OR ', $bag);
    }

    public static function sqlTerbaru(string $tabel, array $kw, string $where, int $batas): string
    {
        $urut = self::urut($kw);

        return 'SELECT * FROM ' . self::q($tabel) . ($where !== '' ? " WHERE {$where}" : '')
            . ($urut !== '' ? " ORDER BY {$urut}" : '') . ' LIMIT ' . max(1, $batas);
    }

    public static function q(string $ident): string
    {
        return '`' . str_replace('`', '``', $ident) . '`';
    }

    // ---------------------------------------------------------------- nilai & baris

    public static function rahasia(string $kolom): bool
    {
        return (bool) preg_match(self::POLA_RAHASIA, explode('#', $kolom, 2)[0]);
    }

    /** Nilai mysqli (string/null/int/float) → nilai aman JSON: biner ditandai, teks panjang dipotong. */
    public static function nilai(mixed $v, int $maksTeks = 300): mixed
    {
        if ($v === null || is_int($v) || is_float($v) || is_bool($v)) {
            return $v;
        }
        $s = (string) $v;
        if (!mb_check_encoding($s, 'UTF-8')) {
            return '<biner ' . strlen($s) . ' byte>';
        }
        if (mb_strlen($s) > $maksTeks) {
            return mb_substr($s, 0, $maksTeks) . '…(+' . (mb_strlen($s) - $maksTeks) . ' karakter)';
        }

        return $s;
    }

    /** Nama kolom kembar (JOIN a.id, b.id) → id, id#2 agar tidak saling timpa di objek JSON. */
    public static function kolomUnik(array $nama): array
    {
        $seen = [];
        $out = [];
        foreach ($nama as $k) {
            $seen[$k] = ($seen[$k] ?? 0) + 1;
            $out[] = $seen[$k] === 1 ? $k : "{$k}#{$seen[$k]}";
        }

        return $out;
    }

    /**
     * Baris mentah (list) → daftar objek terformat; kolom rahasia disamarkan.
     *
     * @param array<int, string> $kolom
     * @param array<int, array<int, mixed>> $baris
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>} [baris, kolom_disamarkan]
     */
    public static function barisDict(array $kolom, array $baris, int $maksTeks = 300): array
    {
        $nama = self::kolomUnik($kolom);
        $samar = array_values(array_filter($nama, [self::class, 'rahasia']));
        $out = [];
        foreach ($baris as $r) {
            $d = [];
            foreach ($nama as $i => $k) {
                $v = $r[$i] ?? null;
                $d[$k] = (in_array($k, $samar, true) && $v !== null) ? '***disamarkan***' : self::nilai($v, $maksTeks);
            }
            $out[] = $d;
        }

        return [$out, $samar];
    }

    /** Kolom yang kosong (null/'') di SEMUA baris, di luar $abaikan. */
    public static function kolomSelaluKosong(array $baris, array $abaikan): array
    {
        if (!$baris) {
            return [];
        }
        $kandidat = null;
        foreach ($baris as $d) {
            $kosong = [];
            foreach ($d as $k => $v) {
                if (!in_array($k, $abaikan, true) && ($v === null || $v === '')) {
                    $kosong[] = $k;
                }
            }
            $kandidat = $kandidat === null ? $kosong : array_values(array_intersect($kandidat, $kosong));
        }

        return $kandidat ?? [];
    }

    /**
     * Catatan konvensi phpVB atas kolom satu tabel (4AGENT-DEVELOP §8, jebakan J08 gov3phpvb).
     *
     * @param array<int, array{nama: string, data_type: string, kunci: string, ekstra: string}> $kolom
     */
    public static function catatanStruktur(array $kolom): array
    {
        $kw = self::kolomWaktu($kolom);
        $ada = array_map(fn($k) => strtolower($k['nama']), $kolom);
        $catatan = [];
        if (!$kw['pk']) {
            $catatan[] = 'tanpa kunci primer tunggal — crudModel phpVB mengandaikan kolom id AUTO_INCREMENT';
        } elseif (strtolower($kw['pk']) !== 'id') {
            $catatan[] = "kunci primer '{$kw['pk']}' bukan 'id' — cek model (crudModel memakai id)";
        }
        if (!in_array('created_by', $ada, true)) {
            $catatan[] = "J08: kolom created_by tidak ada — crudModel::doAdd menyuntiknya, INSERT lewat add gagal 'Unknown column created_by'";
        }
        $audit = array_values(array_intersect(['created_at', 'created_by', 'modify_at', 'modify_by'], $ada));
        if (count($audit) < 4) {
            $catatan[] = 'kolom audit phpVB tidak lengkap: ada [' . implode(', ', $audit) . '], konvensi created_at, created_by, modify_at, modify_by';
        }
        if (in_array('parent_id', $ada, true)) {
            $catatan[] = 'ada parent_id → doAdd memakai jalur hierarkis (level/level_label); tabel datar jangan punya parent_id (J08)';
        }

        return $catatan;
    }

    /** Jaga ukuran balasan dengan membuang baris dari belakang; menambah kunci `dipangkas_ukuran`. */
    public static function pangkas(array $hasil, string $kunci, int $maksChar = 60000): array
    {
        if (!isset($hasil[$kunci]) || !is_array($hasil[$kunci])) {
            return $hasil;
        }
        $awal = count($hasil[$kunci]);
        $catatan = fn(int $n) => "{$n} baris dibuang agar balasan ≤ {$maksChar} karakter — persempit kolom/batas";
        $ukuran = fn(array $h) => strlen(json_encode($h, JSON_UNESCAPED_UNICODE) ?: '');
        if ($ukuran($hasil) <= $maksChar) {
            return $hasil;
        }
        // catatan ikut diukur supaya hasil akhir benar-benar ≤ batas
        while ($hasil[$kunci] && $ukuran($hasil + ['dipangkas_ukuran' => $catatan($awal - count($hasil[$kunci]) + 1)]) > $maksChar) {
            array_pop($hasil[$kunci]);
        }
        $hasil['dipangkas_ukuran'] = $catatan($awal - count($hasil[$kunci]));

        return $hasil;
    }
}
