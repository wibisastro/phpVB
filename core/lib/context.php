<?php

namespace Gov2lib;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Konteks konten lintas-portal (filter Instansi + Wilayah di topbar).
 *
 * Konsep inti: **konteks konten ≠ identitas portal**.
 *
 * | | Identitas portal (tetap) | Konteks konten (ikut filter) |
 * |---|---|---|
 * | Sumber | SERVER_NAME | key sesi ctx_scope / ctx_slug / ctx_unit / ctx_unit_id |
 * | API | fileSource::tenant(), $config->domain->attr['dsn'] | context::slug(), ::unit(), ::path(), ::db(), ::connector() |
 * | Dipakai untuk | sesi, member, options, brand, gating Kancil, SEMUA tulis | PEMBACAAN konten: md, json/csv, tabel app portal lain |
 *
 * Tanpa key ctx_* di sesi, semua getter jatuh ke identitas portal, sehingga
 * path file, SQL, dan tampilan identik dengan sebelum kelas ini ada
 * (no-op untuk 632 portal selain pilot kemenpanrb).
 *
 * Dimuat di core/init/index.php SEBELUM route.php, karena route.php membuat
 * model (→ connectDB) sebelum $self->ses = new gov2session(). Maka cookie
 * Gov2Session di-decode sendiri di sini (JWT HS256 dengan global $publickey,
 * sama seperti gov2session::sesRead) — tetapi TIDAK pernah menyentuh atau
 * menghapus cookie; itu urusan gov2session.
 *
 * Aturan validitas konteks (berlaku di load() maupun set()):
 * - slug/unit cocok ^[a-z0-9_-]+$ (dinormalkan lowercase), scope ∈ SCOPES atau ''
 * - scope '' hanya sah bila slug == fileSource::tenant() (drill unit portal sendiri)
 * - satu saja komponen tak sah → seluruh konteks kosong (tidak ada konteks parsial)
 *
 * Rujukan desain: rencana/filter-konteks-instansi-wilayah.md (bagian 3).
 *
 * @author Wibisono Sastrodiwiryo <wibi@alumni.ui.ac.id>
 * @since 2026-09-08
 */
final class context
{
    /** Nama entri <dsn> pembaca lintas-DB (SELECT-only; <db> di-override per konteks). */
    public const READER_DSN = 'gov3_reader';

    /** DB pusat registry portal, wilayah, dan instansi lintas-portal. */
    public const CENTRAL_DB = 'gov3_central';

    /** Key kanonik di sesi (cookie Gov2Session). */
    public const KEYS = ['ctx_scope', 'ctx_slug', 'ctx_unit', 'ctx_unit_id'];

    /**
     * Nama portal konteks (tampilan saja, bukan bagian validitas konteks):
     * dipakai topbar/panel untuk menunjukkan induk dari unit yang dipilih
     * ("Inspektorat · Kementerian PANRB"). Ditulis oleh penyetel konteks
     * (changeWilayah / changePortal), dihapus bersama KEYS oleh clear().
     */
    public const NAMA_KEY = 'ctx_nama';

    /** Tipe portal = prefiks nama DB ({scope}_{slug}). */
    public const SCOPES = ['kl', 'prov', 'kab', 'kota'];

    /**
     * Role yang boleh memindahkan konteks konten ke portal lain.
     *
     * `guest` ikut serta (keputusan Wibi 2026-09-11): di framework ini guest
     * adalah pengunjung ber-login yang bebas menjelajah, sedangkan `member`
     * terikat pada unitnya — selaras dengan flag `locked` panel Instansi/Wilayah
     * yang hanya mengunci role '' dan `member`.
     */
    public const SWITCH_ROLES = ['guest', 'admin', 'webmaster', 'owner'];

    private const RE_SLUG = '/^[a-z0-9_-]+$/';

    private static bool $loaded = false;
    private static string $scope = '';
    private static string $slug = '';
    private static string $unit = '';
    private static int $unitId = 0;

    /** @var array<string, DBConnector> cache konektor dalam satu request, key "{dsn}|{db}" */
    private static array $connectors = [];

    /**
     * Muat konteks. null → decode cookie Gov2Session; array → pakai langsung
     * (tes, atau setelah sesSave supaya request ini langsung memakai konteks baru).
     * Gagal decode = konteks kosong, tidak fatal.
     */
    public static function load(?array $val = null): void
    {
        if ($val === null) {
            $val = self::decodeCookie();
        }

        $ctx = self::sanitize($val);
        if (!self::valid($ctx)) {
            $ctx = self::emptyContext();
        }

        self::$scope = $ctx['scope'];
        self::$slug = $ctx['slug'];
        self::$unit = $ctx['unit'];
        self::$unitId = $ctx['unit_id'];
        self::$loaded = true;
    }

    /**
     * Buang cache statis (untuk tes / setelah sesi berubah).
     */
    public static function reset(): void
    {
        self::$loaded = false;
        self::$scope = '';
        self::$slug = '';
        self::$unit = '';
        self::$unitId = 0;
        self::$connectors = [];
    }

    /**
     * Konteks aktif = ctx_slug valid terisi.
     */
    public static function active(): bool
    {
        self::ensure();

        return self::$slug !== '';
    }

    public static function scope(): string
    {
        self::ensure();

        return self::$scope;
    }

    public static function slug(): string
    {
        self::ensure();

        return self::$slug;
    }

    public static function unit(): string
    {
        self::ensure();

        return self::$unit;
    }

    public static function unitId(): int
    {
        self::ensure();

        return self::$unitId;
    }

    /**
     * Sub-path folder konten: 'kemenkeu/itjen' | 'kemenkeu' | '' bila tidak aktif.
     * Konvensi unit kerja bersarang di bawah induk karena slug unit tidak unik lintas K/L.
     */
    public static function path(): string
    {
        if (!self::active()) {
            return '';
        }

        return self::$unit !== '' ? self::$slug . '/' . self::$unit : self::$slug;
    }

    /**
     * Slug konten untuk folder: ctx_slug, jatuh ke identitas portal bila tidak aktif.
     */
    public static function tenant(): string
    {
        self::ensure();

        return self::$slug !== '' ? self::$slug : fileSource::tenant();
    }

    /**
     * Nama DB portal konteks: "{scope}_{slug}" (kl_kemenkeu); '' bila scope
     * atau slug kosong (termasuk drill unit portal sendiri dengan scope '').
     */
    public static function db(): string
    {
        self::ensure();

        if (self::$scope === '' || self::$slug === '') {
            return '';
        }

        return self::$scope . '_' . self::$slug;
    }

    /**
     * Konteks menunjuk portal LAIN — app WAJIB menolak semua aksi tulis.
     */
    public static function isForeign(): bool
    {
        return self::active() && self::$slug !== fileSource::tenant();
    }

    /**
     * Mode supervisor: atribut supervisor="1" pada node domain di config instance
     * server (dibaca sebagai $config->domain->attr['supervisor']). Defensif untuk
     * $config SimpleXMLElement maupun stdClass (jalur error config).
     */
    public static function supervisor(): bool
    {
        global $config;

        return trim((string) ($config->domain->attr['supervisor'] ?? '')) === '1';
    }

    /**
     * Boleh memindahkan konteks konten ke portal lain?
     *
     * Perpindahan konteks membuka konten dan DB portal LAIN lewat DSN reader,
     * jadi ia butuh gerbang otorisasi sendiri: panel Instansi/Wilayah adalah
     * endpoint GET yang tidak semuanya memanggil authenticate(), sehingga tanpa
     * cek ini pengunjung anonim pun bisa menyetel ctx_* di portal supervisor.
     *
     * Selaras dengan flag `locked` panel (yang hanya menyembunyikan tombol),
     * tetapi ditegakkan di server: wajib sudah login DAN ber-role SWITCH_ROLES
     * (guest/admin/webmaster/owner); `member` dan role kosong ditolak.
     *
     * @param array<string, mixed> $val isi sesi (gov2session::$val)
     */
    public static function maySwitch(array $val): bool
    {
        if (empty($val['account_id'])) {
            return false;
        }

        return in_array(self::str($val['userRole'] ?? ''), self::SWITCH_ROLES, true);
    }

    /**
     * Gerbang efektif mode supervisor untuk endpoint panel: atribut domain
     * supervisor="1" DAN pengguna berwenang. Portal biasa selalu false, jadi
     * perilaku 632 portal lain tidak berubah.
     *
     * @param array<string, mixed> $val isi sesi (gov2session::$val)
     */
    public static function supervisorFor(array $val): bool
    {
        return self::supervisor() && self::maySwitch($val);
    }

    /**
     * Konektor baca konten sesuai konteks: portal lain → DSN reader dengan DB
     * konteks; selain itu → DSN portal sendiri (identik dengan sebelum ada konteks).
     * Dibuat lazy saat dipanggil; di-cache hanya dalam satu request.
     */
    public static function connector(): DBConnector
    {
        global $config;

        if (self::isForeign()) {
            return self::connect(self::READER_DSN, self::db());
        }

        $dsn = trim((string) ($config->domain->attr['dsn'] ?? ''));

        return self::connect($dsn !== '' ? $dsn : 'master', '');
    }

    /**
     * Konektor ke gov3_central (lookup singkatan wilayah, registry K/L, instansi lintas-portal).
     */
    public static function reader(): DBConnector
    {
        return self::connect(self::READER_DSN, self::CENTRAL_DB);
    }

    /**
     * Tulis 4 key ctx_* ke array sesi (caller yang sesSave, lalu load($val) bila
     * request ini perlu langsung memakai konteks baru). Nilai tak sah → clear().
     */
    public static function set(array &$val, string $scope, string $slug, string $unit = '', int $unitId = 0): void
    {
        $ctx = self::sanitize([
            'scope' => $scope,
            'slug' => $slug,
            'unit' => $unit,
            'unit_id' => $unitId,
        ]);

        if (!self::valid($ctx)) {
            self::clear($val);

            return;
        }

        $val['ctx_scope'] = $ctx['scope'];
        $val['ctx_slug'] = $ctx['slug'];
        $val['ctx_unit'] = $ctx['unit'];
        $val['ctx_unit_id'] = $ctx['unit_id'];
    }

    /**
     * Hapus konteks dari array sesi. Nilai null (bukan unset) supaya
     * sesSave() — yang array_merge dengan token lama — benar-benar menimpa key.
     */
    public static function clear(array &$val): void
    {
        foreach (self::KEYS as $key) {
            $val[$key] = null;
        }
        $val[self::NAMA_KEY] = null;
    }

    /**
     * Simpan nama portal konteks untuk tampilan (teks polos, maks 200 karakter);
     * kosong → null supaya sesSave() menimpa nilai lama.
     */
    public static function setNama(array &$val, string $nama): void
    {
        $nama = trim(strip_tags($nama));
        $nama = function_exists('mb_substr') ? mb_substr($nama, 0, 200) : substr($nama, 0, 200);

        $val[self::NAMA_KEY] = $nama !== '' ? $nama : null;
    }

    /**
     * Validasi bentuk nilai. Menerima key sesi (ctx_*) maupun key polos
     * (scope/slug/unit/unit_id). Satu komponen tak sah → semuanya kosong.
     *
     * @return array{scope: string, slug: string, unit: string, unit_id: int}
     */
    public static function sanitize(array $val): array
    {
        $scope = self::str($val['ctx_scope'] ?? $val['scope'] ?? '');
        $slug = self::str($val['ctx_slug'] ?? $val['slug'] ?? '');
        $unit = self::str($val['ctx_unit'] ?? $val['unit'] ?? '');
        $unitId = self::int($val['ctx_unit_id'] ?? $val['unit_id'] ?? 0);

        if ($slug === '' || !preg_match(self::RE_SLUG, $slug)) {
            return self::emptyContext();
        }

        if ($scope !== '' && !in_array($scope, self::SCOPES, true)) {
            return self::emptyContext();
        }

        if ($unit !== '' && !preg_match(self::RE_SLUG, $unit)) {
            return self::emptyContext();
        }

        return [
            'scope' => $scope,
            'slug' => $slug,
            'unit' => $unit,
            'unit_id' => $unit !== '' && $unitId > 0 ? $unitId : 0,
        ];
    }

    // ------------------------------------------------------------------ privat

    private static function ensure(): void
    {
        if (!self::$loaded) {
            self::load();
        }
    }

    /**
     * Aturan lintas-komponen: slug wajib; scope '' hanya untuk portal sendiri.
     *
     * @param array{scope: string, slug: string, unit: string, unit_id: int} $ctx
     */
    private static function valid(array $ctx): bool
    {
        if ($ctx['slug'] === '') {
            return false;
        }

        return $ctx['scope'] !== '' || $ctx['slug'] === fileSource::tenant();
    }

    /**
     * @return array{scope: string, slug: string, unit: string, unit_id: int}
     */
    private static function emptyContext(): array
    {
        return ['scope' => '', 'slug' => '', 'unit' => '', 'unit_id' => 0];
    }

    /**
     * Decode cookie Gov2Session tanpa efek samping. Cookie basi/garbage/
     * publickey kosong → [] (gov2session yang akan me-reset cookie & log).
     *
     * @return array<string, mixed>
     */
    private static function decodeCookie(): array
    {
        global $publickey;

        $token = $_COOKIE['Gov2Session'] ?? '';
        if (!is_string($token) || $token === '') {
            return [];
        }

        try {
            $decoded = JWT::decode($token, new Key((string) $publickey, 'HS256'));
            $val = json_decode(json_encode($decoded), true);

            return is_array($val) ? $val : [];
        } catch (\Throwable) {
            return [];
        }
    }

    private static function connect(string $dsn, string $db): DBConnector
    {
        return self::$connectors["{$dsn}|{$db}"] ??= new DBConnector($dsn, $db);
    }

    /** Skalar → string lowercase ter-trim; non-skalar (array dari cookie tampered) → ''. */
    private static function str(mixed $v): string
    {
        if (!is_scalar($v)) {
            return '';
        }

        return strtolower(trim((string) $v));
    }

    private static function int(mixed $v): int
    {
        if (!is_scalar($v) || !is_numeric($v)) {
            return 0;
        }

        return (int) $v;
    }
}
