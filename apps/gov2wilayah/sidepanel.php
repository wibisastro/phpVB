<?php

namespace App\gov2wilayah;

use Gov2lib\context;

class sidepanel extends \Gov2lib\api
{
    /**
     * Key sesi instansi legacy (ditulis gov2option/model/index.php::changePortal).
     * Direset saat konteks wilayah berubah karena daftar OPD ikut berganti portal,
     * jadi pilihan instansi lama pasti menunjuk unit kerja pemda yang salah.
     */
    private const INSTANSI_KEYS = [
        'opd_id',
        'opd',
        'portal_nama',
        'unit_nama',
        'unit_id',
        'unit_parent_id',
        'change_portal',
    ];

    /** Wilayah terpilih ada di bawah kab/kota, jadi tidak punya portal konten sendiri. */
    private const CTX_ERR_NO_PORTAL = 'Hanya provinsi/kabupaten/kota yang punya portal konten';

    /** Lookup registry pusat gagal (DSN reader absen, koneksi putus, tabel berubah). */
    private const CTX_ERR_LOOKUP = 'Konteks wilayah tidak dapat dimuat';

    public function __construct()
    {
        global $self;
        parent::__construct();
        $self->scrollInterval = 100;
    }

    public function breadcrumb(array $vars): mixed
    {
        global $self;
        return $self->getBreadcrumb('INDONESIA');
    }

    public function listWilayah(array $vars): mixed
    {
        global $self;
        return $self->getRecords($vars);
    }

    public function children(array $vars): array
    {
        global $self;
        return (array) $self->getChildren((int) ($vars['id'] ?? 0));
    }

    public function getWilayahConfig(): mixed
    {
        global $self, $doc;
        $result = [
            'userRole' => $self->ses->val['userRole'] ?? '',
            'locked' => false,
            'wilayah_nama' => $self->ses->val['wilayah_nama'] ?? '',
            'wilayah_id' => $self->ses->val['wilayah_id'] ?? null,
            'wilayah_level' => $self->ses->val['wilayah_level'] ?? '',
            'wilayah_parent_id' => $self->ses->val['wilayah_parent_id'] ?? null,
            'supervisor' => context::supervisorFor((array) ($self->ses->val ?? [])),
            'ctx' => context::sanitize($self->ses->val),
        ];

        $role = $result['userRole'];
        if ($role === '' || $role === 'member') {
            $result['locked'] = true;
        }

        return $doc->responseGet($result);
    }

    public function changeWilayah(array $vars): mixed
    {
        global $self, $doc;
        $wilayahId = (int) ($vars['id'] ?? 0);
        $nama = $_GET['nama'] ?? '';
        $level = $_GET['level'] ?? '';
        $parentId = (int) ($_GET['parent_id'] ?? 0);
        // Perpindahan konteks membuka DB portal lain lewat reader, jadi
        // gerbangnya bukan hanya atribut domain tapi juga role pengguna
        // (controller ini tidak memanggil authenticate()).
        $supervisor = context::supervisorFor((array) ($self->ses->val ?? []));
        $ctxError = '';
        $instansiCleared = false;

        if ($wilayahId && $nama) {
            // Key legacy tetap ditulis apa adanya (topbar Twig membacanya lewat _SESSION).
            $self->ses->val['wilayah_id'] = $wilayahId;
            $self->ses->val['wilayah_nama'] = $nama;
            $self->ses->val['wilayah_level'] = $level;
            $self->ses->val['wilayah_parent_id'] = $parentId;

            // Hanya mode supervisor yang memetakan wilayah → portal konten.
            // Portal biasa tidak pernah menyentuh ctx_* (no-op untuk 632 portal lain).
            if ($supervisor) {
                $scope = '';
                $slug = '';
                // Snapshot $doc->error: DBConnector melapor lewat exceptionHandler
                // (bukan exception), dan responseGet akan menjadikannya 422 —
                // padahal penulisan legacy di atas tetap harus sukses.
                $errSnapshot = $doc->error;

                try {
                    [$scope, $slug] = self::lookupPortalWilayah($wilayahId);
                } catch (\Throwable $e) {
                    $doc->error = $errSnapshot;
                    $scope = '';
                    $slug = '';
                    $ctxError = self::CTX_ERR_LOOKUP;
                }

                if ($scope !== '' && $slug !== '') {
                    context::set($self->ses->val, $scope, $slug);
                    context::setNama($self->ses->val, is_scalar($nama) ? (string) $nama : '');
                    self::clearInstansi($self->ses->val);
                    $instansiCleared = true;
                } elseif ($ctxError === '') {
                    // Kecamatan/kelurahan/negara: konteks lama sengaja dibiarkan.
                    $ctxError = self::CTX_ERR_NO_PORTAL;
                }
            }

            $self->ses->sesSave($self->ses->val);
        }

        $result = [
            'status' => 'ok',
            'wilayah_id' => $wilayahId,
            'wilayah_nama' => $nama,
            'supervisor' => $supervisor,
            'ctx' => context::sanitize($self->ses->val),
            // Penanda eksplisit: hanya jalur yang benar-benar memanggil
            // clearInstansi() yang boleh membuat UI mengosongkan topbar instansi.
            'instansi_cleared' => $instansiCleared,
        ];

        if ($ctxError !== '') {
            $result['ctx_error'] = $ctxError;
        }

        return $doc->responseGet($result);
    }

    public function resetWilayah(): mixed
    {
        global $self, $doc;
        $supervisor = context::supervisorFor((array) ($self->ses->val ?? []));

        $self->ses->val['wilayah_id'] = null;
        $self->ses->val['wilayah_nama'] = null;
        $self->ses->val['wilayah_level'] = null;
        $self->ses->val['wilayah_parent_id'] = null;

        if ($supervisor) {
            context::clear($self->ses->val);
            self::clearInstansi($self->ses->val);
        }

        $self->ses->sesSave($self->ses->val);

        return $doc->responseGet([
            'status' => 'ok',
            'supervisor' => $supervisor,
            'ctx' => context::sanitize($self->ses->val),
            'instansi_cleared' => $supervisor,
        ]);
    }

    public function searchWilayah(): mixed
    {
        global $self, $doc;
        $keyword = $_GET['q'] ?? '';
        $fields = "id, parent_id, `level`, level_label, nama";
        $result = [];

        try {
            $q = "SELECT {$fields} FROM {$self->tbl->wilayah}
                  WHERE nama LIKE %ss
                  ORDER BY level ASC, nama ASC LIMIT 50";
            $result = \DB::query($q, $keyword);
        } catch (\MeekroDBException $e) {
        }

        return $doc->responseGet($result);
    }

    /**
     * Peta level wilayah → scope portal (prefiks nama DB), logika murni.
     *
     * `wilayah.level_label` tidak membedakan kabupaten dan kota, jadi untuk
     * 'kabupaten' scope diambil dari registry (`monitor_snapshot.scope_level`);
     * bila registry bisu, slug berakhiran 'kota' dipakai sebagai tebakan.
     * Kecamatan/kelurahan/negara tidak punya portal → ''.
     *
     * @param string $levelLabel   nilai kolom wilayah.level_label
     * @param string $slug         wilayah.singkatan (slug portal pemda)
     * @param string $snapshotLevel monitor_snapshot.scope_level ('kab'|'kota'|'')
     * @return string 'prov' | 'kab' | 'kota' | '' (tidak ada portal)
     */
    public static function scopeForWilayah(string $levelLabel, string $slug, string $snapshotLevel = ''): string
    {
        if (strtolower(trim($slug)) === '') {
            return '';
        }

        $label = strtolower(trim($levelLabel));

        if ($label === 'provinsi') {
            return 'prov';
        }

        if ($label !== 'kabupaten') {
            return '';
        }

        $snapshot = strtolower(trim($snapshotLevel));
        if ($snapshot === 'kab' || $snapshot === 'kota') {
            return $snapshot;
        }

        return str_ends_with(strtolower(trim($slug)), 'kota') ? 'kota' : 'kab';
    }

    /**
     * Kosongkan key instansi legacy di array sesi. Nilai null (bukan unset)
     * supaya sesSave() — yang array_merge dengan token lama — benar-benar menimpa.
     *
     * @param array<string, mixed> $val
     */
    public static function clearInstansi(array &$val): void
    {
        foreach (self::INSTANSI_KEYS as $key) {
            $val[$key] = null;
        }
    }

    /**
     * Registry pusat: id wilayah → [scope, slug] portal pemda.
     * ['', ''] bila wilayah tidak ditemukan atau tidak punya portal sendiri.
     * Melempar \Throwable bila DSN reader tidak tersedia (ditangani pemanggil).
     *
     * @return array{0: string, 1: string}
     */
    private static function lookupPortalWilayah(int $wilayahId): array
    {
        // View `wilayah` di DB portal tidak mengekspos kolom singkatan, jadi
        // lookup wajib lewat reader gov3_central.
        $db = context::reader()->db;

        $row = $db->queryFirstRow('SELECT singkatan, level_label FROM wilayah WHERE id=%i LIMIT 1', $wilayahId);
        if (!is_array($row)) {
            return ['', ''];
        }

        $slug = strtolower(trim((string) ($row['singkatan'] ?? '')));
        $label = (string) ($row['level_label'] ?? '');
        if ($slug === '') {
            return ['', ''];
        }

        $snapshot = '';
        if (strtolower(trim($label)) === 'kabupaten') {
            $snapshot = (string) ($db->queryFirstField(
                'SELECT scope_level FROM monitor_snapshot WHERE scope_slug=%s LIMIT 1',
                $slug
            ) ?? '');
        }

        $scope = self::scopeForWilayah($label, $slug, $snapshot);

        return $scope === '' ? ['', ''] : [$scope, $slug];
    }
}
