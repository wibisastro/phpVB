<?php

namespace Gov2lib;

use DB;
use Exception;
use MeekroDBException;
use WhereClause;
use Gov2lib\DBConnector;

/**
 * Options API for managing application options
 *
 * @package Gov2lib
 */
class gov2option
{
    /**
     * Nama tabel options — satu-satunya sumber; berubah ke 'gov2_options'
     * saat jendela migrasi rename prefix (#6134, plan §4.2).
     */
    public const TABLE = 'options';

    /** Versi envelope pinned JSON yang didukung resolver (#6134 note-3) */
    public const PINNED_VERSION = '1.0';

    public mixed $dsn = null;

    /** @var string|null cache hasil dsnDriver() per instance */
    private ?string $driver = null;

    /** @var array<string, array<int, array<string, mixed>>|null> cache pinnedRows() per app */
    private array $pinnedCache = [];
    /**
     * Initialize options handler with database connection
     */
    public function __construct()
    {
        global $doc, $config;
        try {
            $cookies = $doc->envRead($_COOKIE['Gov2Session'] ?? '');
            $this->dsn = $cookies['portal'];
            if (!$this->dsn) {
                $this->dsn = $config->domain->attr['dsn'] ?? 'master';
            }
        } catch (Exception $e) {
            $this->dsn = $config->domain->attr['dsn'] ?? 'master';
        }
    }

    /**
     * Get a single option row from options table
     *
     * @param array $where WHERE clause conditions
     * @param string $whereType AND or OR for WHERE clause
     * @param string[] $select Fields to select
     * @return null|array
     */
    public function get(array $where = [], string $whereType = 'and', array $select = ['id', 'parent_id', 'nama', 'value']): ?array
    {
        global $doc;

        // Preseden tertinggi rantai 4 sumber (#6134): pinned JSON per-portal.
        // Short-circuit per-sumber per-MVC — pinned ada = sumber terpilih,
        // entry miss tidak jatuh ke tier bawah.
        //
        // KECUALI $where tanpa key 'app' (pencarian lintas-app legacy — jalur
        // SQL lama SELECT tanpa filter app): resolusi ikut konvensi
        // cross-calling #6134 — scope saat ini menang, miss → fallback pinned
        // home, masih miss → tier bawah tetap dicari (BC call site krisna_* dkk).
        $appScoped = array_key_exists('app', $where);
        $app = (string) ($where['app'] ?? $GLOBALS['pageID'] ?? '');
        $pinned = $this->pinnedRows($app);

        if ($pinned !== null) {
            $match = self::matchRows($pinned, $where, $whereType)[0] ?? null;

            if ($match || $appScoped) {
                return $match ? array_intersect_key($match, array_flip($select)) : null;
            }
        }

        if (!$appScoped && $app !== 'home') {
            $pinnedHome = $this->pinnedRows('home');
            $match = $pinnedHome === null ? null
                : (self::matchRows($pinnedHome, $where, $whereType)[0] ?? null);

            if ($match) {
                return array_intersect_key($match, array_flip($select));
            }
        }

        // Tier 1 (statis) & tier 3 (supabase): options hanya dari
        // apps/{app}/xml/options.xml — jalur SQL/DBConnector tidak disentuh
        // sama sekali, tanpa file = null silent (T4 #6085)
        if ($this->dsnDriver() !== 'meekro') {
            $xmlRows = $this->xmlRows((string) ($where['app'] ?? $GLOBALS['pageID'] ?? ''));
            $match = $xmlRows === null ? null : (self::matchRows($xmlRows, $where, $whereType)[0] ?? null);

            return $match ? array_intersect_key($match, array_flip($select)) : null;
        }

        dsnSource::requireMeekroDB();
        $select_field = join(',', $select);
        $where_clause = new WhereClause($whereType);

        foreach ($where as $key => $val) {
            $kwarg = gettype($val) === 'string' ? "{$key}=%s" : "{$key}=%i";
            $where_clause->add($kwarg, $val);
        }

        $q = "SELECT {$select_field} FROM " . self::TABLE . " WHERE %l";
        $res = null;

        try {
            $res = DB::queryFirstRow($q, $where_clause);
        } catch (MeekroDBException $e) {
            if ($e->getCode() == 0) {
                $xmlRows = $this->xmlRows((string) ($where['app'] ?? $GLOBALS['pageID'] ?? ''));

                if ($xmlRows !== null) {
                    $match = self::matchRows($xmlRows, $where, $whereType)[0] ?? null;
                    $res = $match ? array_intersect_key($match, array_flip($select)) : null;
                } elseif (self::hasDsnConfig()) {
                    $connector = new DBConnector($this->dsn);
                    $res = $connector->db->queryFirstRow($q, $where_clause);
                }
                // Tier statis (tanpa dsnSource.{stage}.xml dan tanpa options.xml):
                // tidak ada datasource yang dideklarasikan — kembalikan null tanpa error
            } else {
                $doc->exceptionHandler($e->getMessage());
            }
        } catch (Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }

        return $res;
    }

    /**
     * Get option using custom database connector
     *
     * @param array $where WHERE clause conditions
     * @param string $whereType AND or OR for WHERE clause
     * @param string[] $select Fields to select
     * @return null|array
     */
    public function connector_get(array $where = [], string $whereType = 'and', array $select = ['id', 'parent_id', 'nama', 'value']): ?array
    {
        global $doc, $config;
        dsnSource::requireMeekroDB();
        $select_field = join(',', $select);
        $connector = new DBConnector($config->domain->attr['dsn']);
        $where_clause = new WhereClause($whereType);

        foreach ($where as $key => $val) {
            $kwarg = gettype($val) === 'string' ? "{$key}=%s" : "{$key}=%i";
            $where_clause->add($kwarg, $val);
        }

        $q = "SELECT {$select_field} FROM " . self::TABLE . " WHERE %l";
        $res = null;

        try {
            $res = $connector->db->queryFirstRow($q, $where_clause);
        } catch (MeekroDBException $e) {
            $doc->exceptionHandler($e->getMessage());
        } catch (Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }

        return $res;
    }

    /**
     * Create new option record
     *
     * @param array $data Option data
     * @return null|array
     */
    public function create(array $data): ?array
    {
        global $doc;
        $result = null;

        try {
            dsnSource::requireMeekroDB();
            DB::insert(self::TABLE, $data);
            $result = $this->get(['id' => DB::insertId()], 'and',
                ['id', 'parent_id', 'app', 'type', 'nama', 'value', 'status', 'level', 'level_label', 'created_by']);
        } catch (MeekroDBException $e) {
            $doc->exceptionHandler($e->getMessage());
        }

        return $result;
    }

    /**
     * Get all level 1 options for a given app
     *
     * @param string $app Application/pageID
     * @param string $status Filter by status (default: 'on')
     * @return array
     */
    public function getAll(string $app, string $status = 'on'): array
    {
        global $doc;
        $res = [];

        // Preseden tertinggi rantai 4 sumber (#6134): pinned JSON, selaras get()
        $pinned = $this->pinnedRows($app);

        if ($pinned !== null) {
            $select = ['id', 'app', 'type', 'privilege', 'nama', 'keterangan', 'status', 'value'];

            return array_map(
                fn (array $row): array => array_intersect_key($row, array_flip($select)),
                self::matchRows($pinned, ['app' => $app, 'level' => 1, 'status' => $status])
            );
        }

        // Tier 1 & 3: XML-only, selaras get() (T4 #6085)
        if ($this->dsnDriver() !== 'meekro') {
            $xmlRows = $this->xmlRows($app);

            if ($xmlRows === null) {
                return [];
            }

            $select = ['id', 'app', 'type', 'privilege', 'nama', 'keterangan', 'status', 'value'];

            return array_map(
                fn (array $row): array => array_intersect_key($row, array_flip($select)),
                self::matchRows($xmlRows, ['app' => $app, 'level' => 1, 'status' => $status])
            );
        }

        dsnSource::requireMeekroDB();

        $q = "SELECT id, app, type, privilege, nama, keterangan, status, value FROM " . self::TABLE . " WHERE app=%s AND level=1 AND status=%s ORDER BY id ASC";

        try {
            $res = DB::query($q, $app, $status);
        } catch (MeekroDBException $e) {
            if ($e->getCode() == 0) {
                $xmlRows = $this->xmlRows($app);

                if ($xmlRows !== null) {
                    $select = ['id', 'app', 'type', 'privilege', 'nama', 'keterangan', 'status', 'value'];
                    $res = array_map(
                        fn (array $row): array => array_intersect_key($row, array_flip($select)),
                        self::matchRows($xmlRows, ['app' => $app, 'level' => 1, 'status' => $status])
                    );
                } elseif (self::hasDsnConfig()) {
                    $connector = new DBConnector($this->dsn);
                    if (isset($connector->db)) {
                        $res = $connector->db->query($q, $app, $status);
                    }
                }
                // Tier statis: tanpa DSN & tanpa options.xml → options kosong, tanpa error
            }
        } catch (Exception $e) {
            // Silently fail - options are non-critical for page rendering
        }

        return $res ?: [];
    }

    /**
     * Whether the current app declares a database connection at all
     * (apps/{pageID}/xml/dsnSource.{stage}.xml). Absent file = tier statis:
     * the DBConnector retry path is skipped so no-DB apps render without error.
     */
    private static function hasDsnConfig(): bool
    {
        global $pageID;

        $stage = defined('STAGE') ? STAGE : 'dev';

        return file_exists(__DIR__ . "/../../apps/{$pageID}/xml/dsnSource.{$stage}.xml");
    }

    /**
     * Driver DSN app aktif: 'static' (tanpa file DSN), 'meekro', atau
     * 'supabase' — penentu jalur options per tier (T4 #6085). Entri dicari
     * berdasarkan nama DSN instance ($this->dsn); bila tidak ketemu, pakai
     * driver entri pertama. Mengikuti <share> seperti dsnSource::connectDB.
     */
    private function dsnDriver(): string
    {
        global $pageID;

        if ($this->driver !== null) {
            return $this->driver;
        }

        $stage = defined('STAGE') ? STAGE : 'dev';
        $file = __DIR__ . "/../../apps/{$pageID}/xml/dsnSource.{$stage}.xml";

        if (!file_exists($file)) {
            return $this->driver = 'static';
        }

        libxml_use_internal_errors(true);
        $list = simplexml_load_file($file);

        if (is_object($list) && !empty($list->share)) {
            $sharedFile = __DIR__ . "/../../apps/{$list->share}/xml/dsnSource.{$stage}.xml";

            if (file_exists($sharedFile)) {
                $shared = simplexml_load_file($sharedFile);
                $list = is_object($shared) ? $shared : $list;
            }
        }

        libxml_clear_errors();

        if (!is_object($list)) {
            return $this->driver = 'meekro';
        }

        $first = null;

        foreach ($list->dsn as $dsn) {
            $entryDriver = trim((string) $dsn->driver) ?: 'meekro';
            $first ??= $entryDriver;

            if (trim((string) $dsn->name) === trim((string) $this->dsn)) {
                return $this->driver = $entryDriver;
            }
        }

        return $this->driver = $first ?? 'meekro';
    }

    /**
     * Baca pinned JSON (preseden tertinggi rantai 4 sumber, #6134) dari cache
     * lokal disposable per-portal. Hasil di-memo per app per instance — get()
     * dipanggil berulang dalam satu request (getActiveYear, autoregistrasi MVC).
     *
     * @return array<int, array<string, mixed>>|null Null saat tidak ada pinned
     *         atau file invalid (fall-through ke tier berikutnya)
     */
    public function pinnedRows(string $app): ?array
    {
        if (!array_key_exists($app, $this->pinnedCache)) {
            $this->pinnedCache[$app] =
                self::pinnedRowsFromFile(self::pinnedPath((string) $this->dsn, $app), $app);
        }

        return $this->pinnedCache[$app];
    }

    /**
     * Path cache lokal pinned JSON: {GOV2_VAR_DIR | sys_get_temp_dir()+"/gov2var"}
     * /options/{dsn}/{app}.json — dsn = kunci portal (konvensi SERVER_NAME).
     * File disposable: boleh dihapus kapan pun, regenerasi dari kambing (slice C).
     *
     * @return string|null Null bila dsn/app mengandung karakter di luar charset
     *         aman path — dsn berasal dari cookie, app dari URL (anti traversal)
     */
    public static function pinnedPath(string $dsn, string $app): ?string
    {
        foreach ([$dsn, $app] as $segment) {
            if ($segment === '' || preg_match('/[^a-zA-Z0-9_.-]/', $segment) || str_contains($segment, '..')) {
                return null;
            }
        }

        $base = getenv('GOV2_VAR_DIR') ?: sys_get_temp_dir() . '/gov2var';

        return "{$base}/options/{$dsn}/{$app}.json";
    }

    /**
     * Baca + validasi satu file pinned JSON (envelope #6134 note-3):
     * { "gov2options": "1.0", "meta": { "app": ... }, "rows": [...] }.
     *
     * File bisa disentuh admin lewat UI kambing → validasi wajib saat baca:
     * envelope/rows invalid → warning + null (fall-through), bukan fatal.
     * File tidak ada = kondisi normal (portal tanpa pinned), tanpa warning.
     *
     * @return array<int, array<string, mixed>>|null
     */
    public static function pinnedRowsFromFile(?string $file, string $app): ?array
    {
        if ($file === null) {
            return null;
        }

        // Satu kali baca tanpa is_file (hindari double-stat + TOCTOU): file
        // disposable boleh lenyap kapan pun — gagal baca = setara tidak ada,
        // bukan warning "invalid" yang menyesatkan
        $raw = @file_get_contents($file);

        if ($raw === false) {
            return null;
        }

        $data = json_decode($raw, true);

        if (
            !is_array($data)
            || ($data['gov2options'] ?? null) !== self::PINNED_VERSION
            || ($data['meta']['app'] ?? null) !== $app
            || !is_array($data['rows'] ?? null)
        ) {
            error_log("gov2option: pinned JSON invalid (envelope), fall-through: {$file}");

            return null;
        }

        // Row wajib membawa SEMUA kolom options (nilai null boleh — mirror
        // jaminan kolom jalur SQL): matchRows menuntut key ada utk filter
        // app/status/level, dan konsumen (MVC intval($row['value']), select
        // getAll) deref tanpa guard. Kunci hilang → file invalid, fall-through.
        $required = array_flip(['id', 'parent_id', 'app', 'nama', 'type',
            'privilege', 'status', 'value', 'level', 'level_label', 'keterangan']);

        foreach ($data['rows'] as $row) {
            if (!is_array($row) || array_diff_key($required, $row)) {
                error_log("gov2option: pinned JSON invalid (rows), fall-through: {$file}");

                return null;
            }
        }

        return array_values($data['rows']);
    }

    /**
     * Apakah tier efektif saat ini = SQL (driver meekro tanpa pinned aktif)?
     * Satu-satunya kondisi autoregistrasi MVC boleh INSERT (#6134): saat pinned
     * aktif, DB tidak sedang terbaca — menulis ke sana = INSERT berulang tiap
     * request tanpa pernah kelihatan hasilnya.
     */
    public function sqlTierEffective(?string $app = null): bool
    {
        $app ??= (string) ($GLOBALS['pageID'] ?? '');

        return $this->dsnDriver() === 'meekro' && $this->pinnedRows($app) === null;
    }

    /**
     * Load options rows from apps/{app}/xml/options.xml (fallback tier statis
     * — dipakai saat database tidak tersedia; DB tetap menang bila terkonfigurasi).
     *
     * Format file mengikuti wiki 12-Options "XML-based Setup (Tanpa Database)":
     * <options><cluster name="..."><item name="..." value="..."/></cluster></options>
     *
     * @return array<int, array<string, mixed>>|null Null when the file does not exist
     */
    private function xmlRows(string $app): ?array
    {
        if ($app === '' || preg_match('/[^a-zA-Z0-9_-]/', $app)) {
            return null;
        }

        $file = __DIR__ . "/../../apps/{$app}/xml/options.xml";

        if (!file_exists($file)) {
            return null;
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($file);
        libxml_clear_errors();

        return is_object($xml) ? self::flattenOptionsXml($xml, $app) : null;
    }

    /**
     * Flatten cluster/item XML into rows shaped like the options table.
     * Ids are synthetic (document order, 1-based); cluster = level 1,
     * item = level 2 with parent_id pointing at its cluster.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function flattenOptionsXml(\SimpleXMLElement $xml, string $app): array
    {
        $rows = [];
        $id = 0;

        foreach ($xml->cluster as $cluster) {
            $a = $cluster->attributes();
            $clusterId = ++$id;
            $clusterPrivilege = (string) ($a->privilege ?? 'admin');

            $rows[] = [
                'id' => $clusterId,
                'parent_id' => 0,
                'app' => $app,
                'nama' => (string) ($a->name ?? ''),
                'type' => (string) ($a->type ?? 'option'),
                'privilege' => $clusterPrivilege,
                'status' => (string) ($a->status ?? 'on'),
                'value' => (string) ($a->value ?? ''),
                'level' => 1,
                'level_label' => 'cluster',
                'keterangan' => (string) ($a->keterangan ?? ''),
            ];

            foreach ($cluster->item as $item) {
                $b = $item->attributes();

                $rows[] = [
                    'id' => ++$id,
                    'parent_id' => $clusterId,
                    'app' => $app,
                    'nama' => (string) ($b->name ?? ''),
                    'type' => (string) ($b->type ?? 'text'),
                    'privilege' => (string) ($b->privilege ?? $clusterPrivilege),
                    'status' => (string) ($b->status ?? 'on'),
                    'value' => (string) ($b->value ?? ''),
                    'level' => 2,
                    'level_label' => 'option',
                    'keterangan' => (string) ($b->keterangan ?? ''),
                ];
            }
        }

        return $rows;
    }

    /**
     * Filter rows with the same semantics as the SQL WHERE built in get():
     * loose string equality per key, combined with AND (default) or OR.
     * Case-insensitive seperti collation *_ci MySQL yang digantikannya —
     * snapshot pinned membawa data DB apa adanya (status 'ON'/'On' nyata,
     * lih. UPPER(status) di apps/gov2option/model/index.php).
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    public static function matchRows(array $rows, array $where, string $whereType = 'and'): array
    {
        if (empty($where)) {
            return $rows;
        }

        $useOr = strtolower($whereType) === 'or';

        return array_values(array_filter($rows, function (array $row) use ($where, $useOr): bool {
            $hits = 0;

            foreach ($where as $key => $val) {
                if (array_key_exists($key, $row) && strcasecmp((string) $row[$key], (string) $val) === 0) {
                    $hits++;
                }
            }

            return $useOr ? $hits > 0 : $hits === count($where);
        }));
    }

    /**
     * Create MVC instance
     *
     * @return MVC
     */
    public function mvc(): MVC
    {
        return new MVC();
    }

    /**
     * Get gov2option active year
     *
     * @return int year or current year by default if no year options found
     */
    public function getActiveYear(): int
    {
        global $self, $pageID;

        $activeYear = cint(date("Y"));
        $tahun = $self->opt->get(['app' => $pageID, 'nama' => 'Tahun']);

        if ($tahun == null) {
            return $activeYear;
        }

        $activeYearRow = $self->opt->get(['app' => $pageID, 'parent_id' => $tahun['id'], 'value' => '1']);

        if ($activeYearRow) {
            $activeYear = cint($activeYearRow['nama']);
        }

        $member = $this->getMember();

        if (!empty($member)) {
            if (!empty($member['attr'])) {
                $xmlArray = json_decode(json_encode(simplexml_load_string($member['attr'])), true);
                if ($xmlArray['tahun'] ?? false) {
                    $activeYear = $xmlArray['tahun'];
                }
            }
        }

        return $activeYear;
    }

    /**
     * Get member data from database
     *
     * @return null|array
     */
    public function getMember(): ?array
    {
        global $doc, $self;

        $accountId = $self->ses->val['account_id'];
        $_response = null;

        try {
            dsnSource::requireMeekroDB();
            $_query = "SELECT * FROM member WHERE account_id=%i";
            $_response = \DB::queryFirstRow($_query, $accountId);
        } catch (\MeekroDBException $DBException) {
            $this->exceptionHandler($DBException->getMessage());
        }

        return $_response;
    }
}

/**
 * MVC configuration class
 */
class MVC
{
    public int $id = 0;
    public int $parent_id = 0;
    public string $name = '';
    public bool $active = true;
    public bool $recorded = false;

    /**
     * Initialize MVC with optional class name
     */
    public function __construct(string $className = "")
    {
        global $self;
        $this->name = $className ?: $self->className;
        $this->get();
    }

    /**
     * Get or create MVC configuration
     */
    final public function get(): void
    {
        global $self, $pageID;

        $mvc = $self->opt->get(['app' => $pageID, 'nama' => $this->name]);

        if ($mvc) {
            $this->id = intval($mvc['id']);
            $this->parent_id = intval($mvc['parent_id']);
            $this->active = (bool)intval($mvc['value']);
            $this->recorded = (bool)intval($mvc['id']);
        } else {
            $mvc = $this->create();
            if ($mvc) {
                $this->id = intval($mvc['id']);
                $this->parent_id = intval($mvc['parent_id']);
                $this->active = (bool)intval($mvc['value']);
                $this->recorded = (bool)intval($mvc['id']);
            }
        }
    }

    /**
     * Create MVC configuration record
     */
    private function create(): ?array
    {
        global $self, $doc, $pageID;

        // Autoregistrasi hanya saat tier efektif = SQL (#6134): pinned aktif
        // atau driver non-meekro → no-op, jangan INSERT ke DB yang tak terbaca
        if (!$self->opt->sqlTierEffective()) {
            return null;
        }

        $parent_data = [
            'app' => $pageID,
            'nama' => 'MVC',
            'parent_id' => 0,
            'status' => 'on',
            'type' => 'option',
            'level' => 1,
            'level_label' => 'cluster',
            'privilege' => 'admin',
            'keterangan' => 'Enable/Disable MVC',
            'created_by' => $self->ses->val['account_id']
        ];

        $parent = $self->opt->get(['app' => $pageID, 'nama' => 'MVC', 'parent_id' => 0, 'level' => 1]);
        if (!$parent) {
            $parent = $self->opt->create($parent_data);
        }

        $mvc_data = $parent_data;
        $mvc_data['nama'] = $this->name;
        $mvc_data['parent_id'] = $parent['id'];
        $mvc_data['type'] = 'checkbox';
        $mvc_data['level'] = 2;
        $mvc_data['level_label'] = 'option';
        $mvc_data['status'] = 'on';
        $mvc_data['value'] = 1;
        $mvc_data['keterangan'] = 'Check untuk enable MVC ini';

        $mvc = $self->opt->create($mvc_data);
        if (!is_array($doc->error)) {
            return $mvc;
        }

        return null;
    }
}
