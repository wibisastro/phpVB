<?php namespace App\gov2option\model;

use Gov2lib\gov2crypto;
use Gov2lib\mcpClient;
use Gov2lib\optionsImportAdapter;
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
     * Refresh inventori tools satu koneksi gurita: initialize + tools/list →
     * simpan JSON ke kolom `tools` (#6134 slice D). Kegagalan MCP dilaporkan
     * sebagai errors, bukan exception — server gurita = pihak eksternal.
     *
     * @return array{tools?:int, errors?:array<string,string>}
     */
    function discoverTools(int $id, ?mcpClient $client = null): array
    {
        $row = $this->connectionRow($id);

        if ($row === null) {
            return ['errors' => ['id' => 'Koneksi tidak ditemukan']];
        }

        $list = ($client ?? $this->mcpFor($row))->toolsList();

        if ($list['error'] !== null) {
            return ['errors' => ['mcp' => "tools/list gagal: {$list['error']}"]];
        }

        $meta = json_decode((string) ($row['meta'] ?? ''), true) ?: [];
        $meta['tools_discovered_at'] = date('c');

        try {
            \DB::update($this->tbl->table, [
                'tools' => json_encode($list['tools'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'meta' => json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ], 'id=%i', $id);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());

            return ['errors' => ['db' => 'Simpan inventori tools gagal']];
        }

        return ['tools' => count($list['tools'])];
    }

    /**
     * Import gurita → options: tools/call → payload kanonik → adapter →
     * rows options app tujuan (#6134 slice D). Semantik replace-by-cluster:
     * cluster existing dengan nama sama di app tujuan diganti utuh, supaya
     * re-import idempoten ("copy configuration", bukan append).
     *
     * @return array{app?:string, rows?:int, errors?:array<string,string>}
     */
    function importFromTool(
        int $connectionId,
        string $tool,
        array $arguments,
        string $app = '',
        ?int $actorId = null,
        ?mcpClient $client = null
    ): array {
        $row = $this->connectionRow($connectionId);

        if ($row === null) {
            return ['errors' => ['id' => 'Koneksi tidak ditemukan']];
        }

        if ($row['jenis'] !== 'gurita' || $row['status'] !== 'on') {
            return ['errors' => ['id' => 'Koneksi bukan gurita aktif']];
        }

        if ($tool === '') {
            return ['errors' => ['tool' => 'Nama tool wajib diisi']];
        }

        $call = ($client ?? $this->mcpFor($row))->toolsCall($tool, $arguments);

        if ($call['error'] !== null) {
            return ['errors' => ['mcp' => "tools/call gagal: {$call['error']}"]];
        }

        $payload = mcpClient::extractPayload($call['result']);

        if ($payload === null) {
            return ['errors' => ['payload' => 'Hasil tool tidak membawa payload JSON']];
        }

        $adapter = new optionsImportAdapter();
        $issues = $adapter->validate($payload);

        if ($issues) {
            return ['errors' => ['payload' => join('; ', array_slice($issues, 0, 5))]];
        }

        $app = $app !== '' ? $app : (string) ($payload['target']['app'] ?? 'home');

        if (preg_match('/[^a-zA-Z0-9_-]/', $app)) {
            return ['errors' => ['app' => 'Nama app tujuan tidak valid']];
        }

        $rows = $adapter->toRows($payload, [
            'app' => $app,
            'connection_id' => $connectionId,
        ]);

        return $this->persistImportedRows($rows, $app, $actorId);
    }

    /**
     * Tulis rows import ke tabel options dalam satu transaksi: hapus cluster
     * senama (beserta anak-anaknya), lalu INSERT dengan remap id sintetis →
     * id nyata untuk parent_id.
     *
     * @param array<int, array<string, mixed>> $rows hasil optionsImportAdapter::toRows
     * @return array{app?:string, rows?:int, errors?:array<string,string>}
     */
    private function persistImportedRows(array $rows, string $app, ?int $actorId): array
    {
        try {
            \DB::startTransaction();

            $clusterNames = array_column(
                array_filter($rows, fn (array $r): bool => (int) $r['level'] === 1),
                'nama'
            );
            $existing = \DB::queryFirstColumn(
                "SELECT id FROM {$this->tbl->options} WHERE app=%s AND level=1 AND nama IN %ls",
                $app,
                $clusterNames
            );

            if ($existing) {
                \DB::query(
                    "DELETE FROM {$this->tbl->options} WHERE id IN %li OR parent_id IN %li",
                    $existing,
                    $existing
                );
            }

            $idMap = [0 => 0];

            foreach ($rows as $row) {
                $syntheticId = (int) $row['id'];
                unset($row['id']);
                $row['parent_id'] = $idMap[(int) $row['parent_id']] ?? 0;
                $row['metadata'] = json_encode($row['metadata'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $row['created_by'] = $actorId;

                \DB::insert($this->tbl->options, $row);
                $idMap[$syntheticId] = (int) \DB::insertId();
            }

            \DB::commit();
        } catch (\MeekroDBException $e) {
            try {
                \DB::rollback();
            } catch (\MeekroDBException) {
                // koneksi sudah putus — tidak ada yang bisa di-rollback
            }

            $this->exceptionHandler($e->getMessage());

            return ['errors' => ['db' => 'Tulis rows import gagal (periksa kolom metadata — lihat options_table.sql)']];
        }

        return ['app' => $app, 'rows' => count($rows)];
    }

    /** Baris koneksi mentah by id — pemakaian internal (credential ikut terbaca) */
    private function connectionRow(int $id): ?array
    {
        try {
            return \DB::queryFirstRow("SELECT * FROM {$this->tbl->table} WHERE id=%i", $id);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }

        return null;
    }

    /** Client MCP dari row koneksi — credential didekripsi in-memory saja */
    private function mcpFor(array $row): mcpClient
    {
        $credential = empty($row['credential'])
            ? null
            : gov2crypto::decrypt((string) $row['credential']);

        return new mcpClient((string) $row['url'], (string) ($row['auth_type'] ?? 'none'), $credential);
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
