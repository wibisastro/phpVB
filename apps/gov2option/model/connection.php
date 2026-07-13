<?php namespace App\gov2option\model;

use Gov2lib\gov2crypto;
use Gov2lib\pinnedStore;

/**
 * Model registry koneksi gov3 (tabel gov2_connections) — #6134 slice C.
 * Data koneksi = untrusted input (URL dipaste admin): validasi ketat di
 * saveConnection(); credential dienkripsi sodium (gov2crypto, key dari env
 * instance) dan TIDAK pernah ikut keluar lewat listConnections().
 */
class connection extends \Gov2lib\crudHandler
{
    /** Jenis komponen gov3 yang dikenal — VARCHAR di DB, whitelist di kode */
    public const JENIS = ['gurita', 'kambing', 'lebah'];
    public const AUTH_TYPES = ['none', 'bearer', 'basic', 'apikey'];

    function __construct()
    {
        global $doc, $config;
        $this->templateDir = __DIR__ . "/../view";
        $path = explode("\\", __CLASS__);
        $this->className = $path[sizeof($path) - 1];
        $doc->body("className", $this->className);
        parent::__construct($config->domain->attr['dsn']);
        $this->tbl->table = $this->tbl->connections;
    }

    /** Daftar koneksi TANPA kolom credential (jangan pernah bocor ke klien) */
    function listConnections(): array
    {
        $q = "SELECT id, jenis, nama, url, status, auth_type,
                     (credential IS NOT NULL) AS has_credential,
                     (tools IS NOT NULL) AS has_tools,
                     meta, created_at, modify_at
              FROM {$this->tbl->table} ORDER BY jenis, nama";

        try {
            return \DB::query($q) ?: [];
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }

        return [];
    }

    /**
     * Insert/update satu koneksi. Data dari admin = untrusted:
     * jenis & auth_type whitelist, nama/url wajib + size cap, url valid http(s).
     *
     * @return array{id?:int, errors?:array<string,string>}
     */
    function saveConnection(array $data, ?int $actorId = null): array
    {
        $errors = [];
        $jenis = trim((string) ($data['jenis'] ?? ''));
        $nama = trim((string) ($data['nama'] ?? ''));
        $url = trim((string) ($data['url'] ?? ''));
        $authType = trim((string) ($data['auth_type'] ?? 'none')) ?: 'none';
        $status = ($data['status'] ?? 'on') === 'off' ? 'off' : 'on';

        if (!in_array($jenis, self::JENIS, true)) {
            $errors['jenis'] = 'Jenis harus salah satu: ' . join(', ', self::JENIS);
        }

        if ($nama === '' || strlen($nama) > 190) {
            $errors['nama'] = 'Nama wajib diisi (maks 190 karakter)';
        }

        if (strlen($url) > 255 || !filter_var($url, FILTER_VALIDATE_URL)
            || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            $errors['url'] = 'URL harus http(s) valid (maks 255 karakter)';
        }

        if (!in_array($authType, self::AUTH_TYPES, true)) {
            $errors['auth_type'] = 'auth_type harus salah satu: ' . join(', ', self::AUTH_TYPES);
        }

        $row = [
            'jenis' => $jenis,
            'nama' => $nama,
            'url' => $url,
            'status' => $status,
            'auth_type' => $authType,
        ];

        // Credential opsional; bila dikirim → wajib terenkripsi (fail-closed:
        // tanpa GOV2_CRED_KEY penyimpanan ditolak, bukan jatuh ke plaintext)
        $credential = (string) ($data['credential'] ?? '');

        if ($credential !== '') {
            $sealed = gov2crypto::encrypt($credential);

            if ($sealed === null) {
                $errors['credential'] = 'Enkripsi credential gagal — GOV2_CRED_KEY belum di-set di env instance';
            } else {
                $row['credential'] = $sealed;
            }
        }

        if ($errors) {
            return ['errors' => $errors];
        }

        $id = (int) ($data['id'] ?? 0);

        try {
            if ($id > 0) {
                $row['modify_by'] = $actorId;
                \DB::update($this->tbl->table, $row, 'id=%i', $id);
            } else {
                $row['created_by'] = $actorId;
                \DB::insert($this->tbl->table, $row);
                $id = (int) \DB::insertId();
            }
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());

            return ['errors' => ['db' => 'Simpan gagal']];
        }

        return ['id' => $id];
    }

    function deleteConnection(int $id): int
    {
        try {
            \DB::delete($this->tbl->table, 'id=%i', $id);

            return \DB::affectedRows();
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }

        return 0;
    }

    /** Credential terdekripsi — pemakaian internal (klien MCP slice D), tak pernah via HTTP */
    function credential(int $id): ?string
    {
        try {
            $sealed = \DB::queryFirstField("SELECT credential FROM {$this->tbl->table} WHERE id=%i", $id);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());

            return null;
        }

        return $sealed === null ? null : gov2crypto::decrypt((string) $sealed);
    }

    /**
     * Save-to-lower-tier satu MVC utuh: rows DB app → envelope pinned →
     * PUT kambing → refresh cache lokal (#6134 keputusan 6).
     *
     * @return array{remote?:string, cache?:string, rows?:int, errors?:array<string,string>}
     */
    function pinApp(string $app, ?int $actorId = null): array
    {
        global $config;

        if ($app === '' || preg_match('/[^a-zA-Z0-9_-]/', $app)) {
            return ['errors' => ['app' => 'Nama app tidak valid']];
        }

        try {
            $rows = \DB::query("SELECT * FROM {$this->tbl->options} WHERE app=%s ORDER BY id ASC", $app);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());

            return ['errors' => ['db' => 'Baca rows options gagal']];
        }

        if (!$rows) {
            return ['errors' => ['app' => "Tidak ada rows options untuk app '{$app}'"]];
        }

        // dsn dari instance config (identitas portal), BUKAN cookie — jalur
        // tulis tidak boleh dibelokkan sesi
        $dsn = (string) ($config->domain->attr['dsn'] ?? '');
        $envelope = pinnedStore::buildEnvelope($rows, $app, $actorId, "sql:{$dsn}");
        $result = (new pinnedStore())->save($dsn, $app, $envelope);
        $result['rows'] = count($envelope['rows']);

        return $result;
    }

    function dependencies()
    {
    }
}
