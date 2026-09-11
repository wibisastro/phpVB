<?php namespace App\gov2option\model;

class index extends \Gov2lib\crudHandler {
	function __construct ($dsn="") {
	    global $config;
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
		$this->controller=__DIR__."/../".$this->className.".php";
        if (!$dsn) {$dsn=$config->domain->attr['dsn'];}
        parent::__construct($dsn);
        // $this->tbl->table=$this->tbl->dp_draft;
	}

    function getList() {
        global $self;
        $q = "SELECT DISTINCT(app) FROM {$this->tbl->options} WHERE level=1 AND type='option' AND UPPER(status)='ON'";
        $qs = "SELECT DISTINCT(app) FROM {$this->tbl->options} WHERE level=1 AND type='service' AND UPPER(status)='ON'";
        $res = [];

        try {
            $res['options'] = \DB::query($q);
            $res['services'] = \DB::query($qs);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }

        $res['userRole'] = $self->ses->val['userRole'] ?? '';
        return $res;
    }

    #---coded by claude
    function getYearOptions(string $pageID): array
    {
        global $self;
        $res = ['optionTahun' => [], 'activeYear' => null];

        if (empty($pageID)) {
            return $res;
        }

        $q = "SELECT child.nama, child.value as is_active
              FROM {$this->tbl->options} parent
              JOIN {$this->tbl->options} child ON child.parent_id = parent.id
              WHERE parent.app=%s AND parent.nama LIKE '%Tahun%' AND parent.level=1
              ORDER BY child.nama DESC";

        try {
            $rows = \DB::query($q, $pageID);
            foreach ($rows as $row) {
                $res['optionTahun'][] = ['nama' => $row['nama']];
                if ($row['is_active'] == '1' && $res['activeYear'] === null) {
                    $res['activeYear'] = $row['nama'];
                }
            }
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }

        // Fallback: activeYear from member attr
        if ($res['activeYear'] === null && !empty($res['optionTahun'])) {
            $member = $self->opt->getMember();
            if (!empty($member['attr'])) {
                $xmlArray = json_decode(json_encode(simplexml_load_string($member['attr'])), true);
                if (!empty($xmlArray['tahun'])) {
                    $res['activeYear'] = (string)$xmlArray['tahun'];
                }
            }
        }

        return $res;
    }

    #---coded by claude
    function setYear(string $pageID, string $year): void
    {
        global $self;

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $member = $dom->createElement('member');
        $tahun = $dom->createElement('tahun', htmlspecialchars($year, ENT_XML1));
        $member->appendChild($tahun);
        $dom->appendChild($member);
        $dom->formatOutput = true;
        $xmlString = $dom->saveXML();

        try {
            \DB::update('member', ['attr' => $xmlString], 'account_id=%i', $self->ses->val['account_id']);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }
    }

    // ------------------------------------------------------------------
    //  Daftar instansi konteks-sensitif
    //  Rujukan: rencana/filter-konteks-instansi-wilayah.md (bagian 3.5)
    //
    //  Mode supervisor (atribut supervisor="1" pada node domain di config
    //  instance server, dibaca lewat \Gov2lib\context::supervisor()) mengubah
    //  SUMBER daftar: bukan view `instansi` portal sendiri, melainkan
    //  gov3_central lewat DSN reader (SELECT-only, lihat context::reader()).
    //  Tanpa atribut itu seluruh method di bawah berperilaku persis seperti
    //  sebelumnya — no-op untuk 632 portal lain.
    //
    //  Node portal virtual (satu K/L) dikirim lewat query string ?node=kl:{slug},
    //  BUKAN segmen path, supaya route /gov2option/index/{cmd}[/{id}] tetap.
    // ------------------------------------------------------------------

    /** Pola slug portal/unit — sama dengan Gov2lib\context. */
    private const RE_SLUG = '/^[a-z0-9_-]+$/';

    /** Pemisah pada node portal virtual ("kl:kemenkeu"). */
    private const NODE_SEP = ':';

    /** Scope portal yang berarti pemerintah daerah (punya daftar OPD). */
    private const SCOPES_DAERAH = ['prov', 'kab', 'kota'];

    /**
     * Normalkan slug portal/unit: lowercase, ter-trim, wajib ^[a-z0-9_-]+$.
     * Nilai tak sah (path traversal, spasi, array dari query string) → ''.
     */
    public static function normSlug(mixed $slug): string
    {
        if (!is_scalar($slug)) {
            return '';
        }

        $slug = strtolower(trim((string) $slug));

        return preg_match(self::RE_SLUG, $slug) ? $slug : '';
    }

    /**
     * Normalkan scope portal: wajib salah satu \Gov2lib\context::SCOPES.
     */
    public static function normScope(mixed $scope): string
    {
        if (!is_scalar($scope)) {
            return '';
        }

        $scope = strtolower(trim((string) $scope));

        return in_array($scope, \Gov2lib\context::SCOPES, true) ? $scope : '';
    }

    /**
     * Urai node portal virtual "kl:kemenkeu" → ['scope' => 'kl', 'slug' => 'kemenkeu'].
     * Bentuk tak sah → keduanya ''.
     *
     * @return array{scope: string, slug: string}
     */
    public static function parseNode(mixed $node): array
    {
        $kosong = ['scope' => '', 'slug' => ''];

        if (!is_scalar($node)) {
            return $kosong;
        }

        $node = trim((string) $node);
        if ($node === '' || !str_contains($node, self::NODE_SEP)) {
            return $kosong;
        }

        [$scope, $slug] = explode(self::NODE_SEP, $node, 2);
        $scope = self::normScope($scope);
        $slug = self::normSlug($slug);

        if ($scope === '' || $slug === '') {
            return $kosong;
        }

        return ['scope' => $scope, 'slug' => $slug];
    }

    /**
     * Node virtual satu portal K/L pada pohon instansi. Field lama (id,
     * parent_id, level, nama, kode, portal, has_children) tetap ada supaya
     * pemakai JSON lama tidak pecah; slug/scope/is_portal/node aditif.
     *
     * @return array<string, mixed>
     */
    public static function klNode(string $slug, string $nama): array
    {
        return [
            'id' => 0,
            'node' => 'kl' . self::NODE_SEP . $slug,
            'parent_id' => 0,
            'level' => 0,
            'nama' => $nama !== '' ? $nama : $slug,
            'kode' => $slug,
            'portal' => $slug,
            'slug' => $slug,
            'scope' => 'kl',
            'is_portal' => true,
            'has_children' => true,
        ];
    }

    /**
     * Lengkapi baris gov3_central.instansi dengan asal portalnya (aditif).
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function decorateRow(array $row, string $slug, string $scope): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['parent_id'] = (int) ($row['parent_id'] ?? 0);
        $row['slug'] = $slug;
        $row['scope'] = $scope;
        $row['is_portal'] = false;
        $row['node'] = '';

        return $row;
    }

    /**
     * Sebutan sub-organisasi untuk judul/placeholder panel: "Unit Kerja" bila
     * konteks K/L atau supervisor tanpa konteks (daftar = registry K/L),
     * selain itu "OPD".
     */
    public static function subOrgTerm(bool $supervisor, string $scope, bool $active): string
    {
        if ($scope === 'kl' || ($supervisor && !$active)) {
            return 'Unit Kerja';
        }

        return 'OPD';
    }

    #---coded by claude
    function getUnitKerjaList(array|int $vars = [])
    {
        global $doc;
        $parentId = is_array($vars) ? (int)($vars['id'] ?? 0) : (int)$vars;

        if ($this->supervisorMode()) {
            $rows = $this->unitKerjaListSupervisor($parentId);
            if ($rows !== null) {
                return $doc->responseGet($rows);
            }
        }

        $fields = "id, parent_id, `level`, nama, kode, portal";
        $result = [];

        try {
            if ($parentId > 0) {
                $q = "SELECT {$fields} FROM {$this->tbl->instansi} WHERE parent_id=%i ORDER BY kode ASC";
                $result = \DB::query($q, $parentId);
            } else {
                $q = "SELECT {$fields} FROM {$this->tbl->instansi} WHERE parent_id=0 ORDER BY kode ASC";
                $result = \DB::query($q);
            }
            foreach ($result as $i => $row) {
                $qc = "SELECT COUNT(*) FROM {$this->tbl->instansi} WHERE parent_id=%i";
                $cnt = \DB::queryFirstField($qc, $row['id']);
                $result[$i]['has_children'] = (int)$cnt > 0;
            }
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }

        return $doc->responseGet($result);
    }

    #---coded by claude
    function searchUnitKerja($vars = [])
    {
        global $doc;
        $keyword = $_GET['q'] ?? '';
        $fields = "id, parent_id, `level`, nama, kode, portal";
        $result = [];

        if ($this->supervisorMode()) {
            $rows = $this->searchUnitKerjaSupervisor(is_scalar($keyword) ? (string) $keyword : '');
            if ($rows !== null) {
                return $doc->responseGet($rows);
            }
        }

        try {
            $q = "SELECT {$fields} FROM {$this->tbl->instansi}
                  WHERE (nama LIKE %ss OR kode LIKE %ss)
                  ORDER BY kode ASC LIMIT 50";
            $result = \DB::query($q, $keyword, $keyword);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }

        return $doc->responseGet($result);
    }

    #---coded by claude
    function getUnitKerjaConfig($vars = [])
    {
        global $self, $doc;
        $result = ['userRole' => '', 'locked' => false, 'unit_nama' => '', 'unit_id' => null, 'parent_id' => null, 'portal' => ''];

        $result['userRole'] = $self->ses->val['userRole'] ?? '';
        $result['unit_nama'] = $self->ses->val['unit_nama'] ?? '';
        $result['unit_id'] = $self->ses->val['opd_id'] ?? null;
        $result['parent_id'] = $self->ses->val['unit_parent_id'] ?? null;
        $result['portal'] = $self->ses->val['opd'] ?? '';

        $role = $result['userRole'];
        if ($role === '' || $role === 'member') {
            $result['locked'] = true;
        }

        //--- aditif: konteks konten + label panel (default = perilaku lama)
        $supervisor = $this->supervisorMode();
        $ctx = $this->contextInfo();
        $result['supervisor'] = $supervisor;
        $result['ctx'] = $ctx;
        $result['sub_org_term'] = self::subOrgTerm($supervisor, $ctx['scope'], $ctx['active']);
        $result['wilayah_nama'] = $self->ses->val['wilayah_nama'] ?? '';

        return $doc->responseGet($result);
    }

    #---coded by claude
    function changePortal($vars = [])
    {
        global $self, $doc;
        $vars = is_array($vars) ? $vars : [];
        $unitId = (int)($vars['id'] ?? 0);
        $portal = $_GET['portal'] ?? '';
        $unitNama = $_GET['nama'] ?? '';
        $parentId = (int)($_GET['parent_id'] ?? 0);

        $supervisor = $this->supervisorMode();
        $node = self::parseNode($_GET['node'] ?? '');

        //--- (A) node portal virtual (K/L dari registry) — hanya mode supervisor
        if ($supervisor && $node['slug'] !== '') {
            return $doc->responseGet(
                $this->pilihPortalNode($node['scope'], $node['slug'], is_scalar($unitNama) ? (string) $unitNama : '')
            );
        }

        //--- (B) node unit/OPD (id numerik) — gerbang legacy dipertahankan persis
        $res = ['status' => 'ok', 'unit_id' => $unitId, 'unit_nama' => $unitNama];

        if ($unitId && $unitNama) {
            // Hanya mode supervisor yang menulis ctx_*. Portal biasa TIDAK boleh
            // punya konteks: pipeline publikasi belum menulis folder unit
            // bersarang (json/{slug}/{unit}/), jadi konteks unit lokal akan
            // membuat konten app (mis. aisakip) hilang di 632 portal lain.
            if ($supervisor) {
                $this->pasangKonteksUnitPusat($unitId, $res);
            }

            $self->ses->val['opd_id'] = $unitId;
            $self->ses->val['opd'] = $portal;
            $self->ses->val['portal_nama'] = $unitNama;
            $self->ses->val['unit_nama'] = $unitNama;
            $self->ses->val['unit_id'] = $unitId;
            $self->ses->val['unit_parent_id'] = $parentId;
            $self->ses->val['change_portal'] = 1;
            $self->ses->sesSave($self->ses->val);
            $this->muatUlangKonteks();
        }

        $res['is_portal'] = false;
        $res['ctx'] = $this->contextInfo();

        return $doc->responseGet($res);
    }

    #---coded by claude
    function resetPortal($vars = [])
    {
        global $self, $doc;
        $self->ses->val['opd_id'] = null;
        $self->ses->val['opd'] = null;
        $self->ses->val['portal_nama'] = null;
        $self->ses->val['unit_nama'] = null;
        $self->ses->val['unit_id'] = null;
        $self->ses->val['unit_parent_id'] = null;
        $self->ses->val['change_portal'] = null;

        //--- supervisor + wilayah legacy masih aktif → konteks pemda dipertahankan,
        //    hanya unit/OPD-nya yang dilepas (kembali ke induk pemda).
        $indukDipertahankan = false;
        if ($this->supervisorMode() && !empty($self->ses->val['wilayah_id'])) {
            $scope = $this->ctxNilai('scope');
            $slug = $this->ctxNilai('slug');
            if ($scope !== '' && $slug !== '') {
                try {
                    \Gov2lib\context::set($self->ses->val, $scope, $slug, '', 0);
                    $indukDipertahankan = true;
                } catch (\Throwable $e) {
                    error_log('gov2option: gagal mempertahankan konteks induk saat reset (' . $e->getMessage() . ')');
                }
            }
        }

        if (!$indukDipertahankan) {
            \Gov2lib\context::clear($self->ses->val);
        }

        $self->ses->sesSave($self->ses->val);
        $this->muatUlangKonteks();

        return $doc->responseGet([
            'status' => 'ok',
            'kept_parent' => $indukDipertahankan,
            'ctx' => $this->contextInfo(),
        ]);
    }

    // -------------------------------------------------- helper konteks (privat)

    /**
     * Mode supervisor EFEKTIF: atribut domain supervisor="1" DAN pengguna
     * berwenang (context::SWITCH_ROLES — guest/admin/webmaster/owner; member
     * ditolak). Tanpa gerbang role, akun member yang UI-nya `locked` masih bisa
     * memanggil endpoint GET ini langsung dan membuka konten 633 portal.
     * Defensif terhadap config yang belum siap.
     */
    private function supervisorMode(): bool
    {
        global $self;

        try {
            return \Gov2lib\context::supervisorFor((array) ($self->ses->val ?? []));
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Ringkasan konteks aktif untuk respons JSON (aditif).
     *
     * @return array{active: bool, scope: string, slug: string, unit: string, unit_id: int, foreign: bool}
     */
    private function contextInfo(): array
    {
        try {
            return [
                'active' => \Gov2lib\context::active(),
                'scope' => \Gov2lib\context::scope(),
                'slug' => \Gov2lib\context::slug(),
                'unit' => \Gov2lib\context::unit(),
                'unit_id' => \Gov2lib\context::unitId(),
                'foreign' => \Gov2lib\context::isForeign(),
            ];
        } catch (\Throwable $e) {
            return ['active' => false, 'scope' => '', 'slug' => '', 'unit' => '', 'unit_id' => 0, 'foreign' => false];
        }
    }

    /** Satu getter konteks, aman bila context gagal dimuat. */
    private function ctxNilai(string $key): string
    {
        $ctx = $this->contextInfo();

        return is_string($ctx[$key] ?? null) ? $ctx[$key] : '';
    }

    /**
     * Sinkronkan cache statis context dengan sesi yang baru disimpan supaya
     * request ini (dan respons JSON-nya) sudah memakai konteks baru.
     */
    private function muatUlangKonteks(): void
    {
        global $self;

        try {
            \Gov2lib\context::load((array) ($self->ses->val ?? []));
        } catch (\Throwable $e) {
            error_log('gov2option: gagal memuat ulang konteks (' . $e->getMessage() . ')');
        }
    }

    /**
     * Daftar instansi mode supervisor. null = gagal/ tak ada acuan → pemanggil
     * jatuh ke daftar portal sendiri (perilaku lama).
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function unitKerjaListSupervisor(int $parentId): ?array
    {
        try {
            $node = self::parseNode($_GET['node'] ?? '');
            $slugParam = self::normSlug($_GET['slug'] ?? '');

            //--- buka node portal virtual → eselon-1 K/L tersebut
            if ($node['slug'] !== '') {
                return $this->instansiRows($node['slug'], 0, $node['scope']);
            }

            $active = \Gov2lib\context::active();
            $scope = \Gov2lib\context::scope();
            $slug = \Gov2lib\context::slug();

            //--- drill anak dari node numerik
            if ($parentId > 0) {
                $target = $slugParam !== '' ? $slugParam : $slug;
                if ($target === '') {
                    return null;
                }
                $targetScope = $target === $slug ? $scope : $this->scopeOfSlug($target);

                return $this->instansiRows($target, $parentId, $targetScope);
            }

            //--- root: konteks daerah → OPD pemda; selain itu registry K/L
            if ($active && in_array($scope, self::SCOPES_DAERAH, true)) {
                return $this->instansiRows($slug, 0, $scope);
            }

            return $this->klRows();
        } catch (\Throwable $e) {
            error_log('gov2option: daftar instansi supervisor gagal (' . $e->getMessage() . ') - memakai daftar portal sendiri');

            return null;
        }
    }

    /**
     * Pencarian mode supervisor. null = gagal → pemanggil jatuh ke query lama.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function searchUnitKerjaSupervisor(string $keyword): ?array
    {
        try {
            $db = \Gov2lib\context::reader()->db;

            if (\Gov2lib\context::active()) {
                $slug = \Gov2lib\context::slug();
                $scope = \Gov2lib\context::scope();
                $rows = (array) $db->query(
                    "SELECT id, parent_id, `level`, nama, kode, portal FROM instansi
                     WHERE singkatan=%s AND (nama LIKE %ss OR kode LIKE %ss)
                     ORDER BY kode ASC LIMIT 50",
                    $slug,
                    $keyword,
                    $keyword
                );

                $out = [];
                foreach ($rows as $row) {
                    $out[] = self::decorateRow((array) $row, $slug, $scope);
                }

                return $out;
            }

            $rows = (array) $db->query(
                "SELECT scope_slug, COALESCE(NULLIF(scope_name,''), scope_slug) AS nama
                 FROM monitor_snapshot
                 WHERE scope_level='kl' AND (scope_name LIKE %ss OR scope_slug LIKE %ss)
                 ORDER BY nama ASC LIMIT 50",
                $keyword,
                $keyword
            );

            $out = [];
            foreach ($rows as $row) {
                $slug = self::normSlug($row['scope_slug'] ?? '');
                if ($slug === '') {
                    continue;
                }
                $out[] = self::klNode($slug, trim((string) ($row['nama'] ?? '')));
            }

            return $out;
        } catch (\Throwable $e) {
            error_log('gov2option: pencarian instansi supervisor gagal (' . $e->getMessage() . ') - memakai query portal sendiri');

            return null;
        }
    }

    /**
     * Registry K/L dari gov3_central.monitor_snapshot sebagai node virtual.
     *
     * @return array<int, array<string, mixed>>
     */
    private function klRows(): array
    {
        $db = \Gov2lib\context::reader()->db;
        $rows = (array) $db->query(
            "SELECT scope_slug, COALESCE(NULLIF(scope_name,''), scope_slug) AS nama
             FROM monitor_snapshot WHERE scope_level='kl' ORDER BY nama ASC"
        );

        $out = [];
        foreach ($rows as $row) {
            $slug = self::normSlug($row['scope_slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $out[] = self::klNode($slug, trim((string) ($row['nama'] ?? '')));
        }

        return $out;
    }

    /**
     * Anak instansi satu portal dari gov3_central (satu query hitung anak,
     * bukan N+1 seperti jalur lama).
     *
     * @return array<int, array<string, mixed>>
     */
    private function instansiRows(string $slug, int $parentId, string $scope): array
    {
        $slug = self::normSlug($slug);
        if ($slug === '') {
            return [];
        }

        $db = \Gov2lib\context::reader()->db;
        $rows = (array) $db->query(
            "SELECT id, parent_id, `level`, nama, kode, portal FROM instansi
             WHERE singkatan=%s AND parent_id=%i ORDER BY kode ASC",
            $slug,
            $parentId
        );

        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        $jmlAnak = [];
        if ($ids) {
            $counts = (array) $db->query(
                "SELECT parent_id, COUNT(*) AS jml FROM instansi
                 WHERE singkatan=%s AND parent_id IN %li GROUP BY parent_id",
                $slug,
                $ids
            );
            foreach ($counts as $c) {
                $jmlAnak[(int) ($c['parent_id'] ?? 0)] = (int) ($c['jml'] ?? 0);
            }
        }

        $out = [];
        foreach ($rows as $row) {
            $row = self::decorateRow((array) $row, $slug, $scope);
            $row['has_children'] = ($jmlAnak[$row['id']] ?? 0) > 0;
            $out[] = $row;
        }

        return $out;
    }

    /**
     * scope_level portal dari registry pusat; '' bila tidak terdaftar.
     */
    private function scopeOfSlug(string $slug): string
    {
        $slug = self::normSlug($slug);
        if ($slug === '') {
            return '';
        }

        try {
            $db = \Gov2lib\context::reader()->db;
            $level = $db->queryFirstField(
                "SELECT scope_level FROM monitor_snapshot WHERE scope_slug=%s LIMIT 1",
                $slug
            );
        } catch (\Throwable $e) {
            error_log('gov2option: lookup scope portal gagal (' . $e->getMessage() . ')');

            return '';
        }

        return self::normScope($level);
    }

    /**
     * Pilih satu portal (node virtual K/L): konteks pindah ke portal itu,
     * key legacy diisi untuk topbar, dan filter wilayah dilepas karena tidak
     * relevan untuk K/L.
     *
     * @return array<string, mixed>
     */
    private function pilihPortalNode(string $scope, string $slug, string $nama): array
    {
        global $self;

        $nama = trim($nama) !== '' ? trim($nama) : $slug;

        \Gov2lib\context::set($self->ses->val, $scope, $slug, '', 0);

        $self->ses->val['opd_id'] = 0;
        $self->ses->val['opd'] = $slug;
        $self->ses->val['portal_nama'] = $nama;
        $self->ses->val['unit_nama'] = $nama;
        $self->ses->val['unit_id'] = 0;
        $self->ses->val['unit_parent_id'] = 0;
        $self->ses->val['change_portal'] = 1;

        $wilayahDilepas = false;
        if ($scope === 'kl') {
            $self->ses->val['wilayah_id'] = null;
            $self->ses->val['wilayah_nama'] = null;
            $self->ses->val['wilayah_level'] = null;
            $self->ses->val['wilayah_parent_id'] = null;
            $wilayahDilepas = true;
        }

        $self->ses->sesSave($self->ses->val);
        $this->muatUlangKonteks();

        return [
            'status' => 'ok',
            'unit_id' => 0,
            'unit_nama' => $nama,
            'is_portal' => true,
            'wilayah_cleared' => $wilayahDilepas,
            'ctx' => $this->contextInfo(),
        ];
    }

    /**
     * Konteks untuk unit/OPD mode supervisor: slug portal diambil dari
     * gov3_central.instansi.singkatan, scope dari konteks berjalan atau
     * registry. Gagal lookup → konteks tidak diubah (catat di respons).
     *
     * @param array<string, mixed> $res
     */
    private function pasangKonteksUnitPusat(int $unitId, array &$res): void
    {
        global $self;

        try {
            $db = \Gov2lib\context::reader()->db;
            $row = $db->queryFirstRow(
                "SELECT singkatan, portal, nama FROM instansi WHERE id=%i",
                $unitId
            );
        } catch (\Throwable $e) {
            error_log('gov2option: lookup unit pusat gagal (' . $e->getMessage() . ')');
            $res['notice'] = 'Registry pusat tidak terbaca; konteks tidak diubah';

            return;
        }

        if (!$row) {
            $res['notice'] = 'Unit tidak ada di registry pusat; konteks tidak diubah';

            return;
        }

        $slug = self::normSlug($row['singkatan'] ?? '');
        if ($slug === '') {
            $res['notice'] = 'Unit tanpa slug portal induk; konteks tidak diubah';

            return;
        }

        $ctxSlug = $this->ctxNilai('slug');
        $ctxScope = $this->ctxNilai('scope');
        $scope = ($slug === $ctxSlug && $ctxScope !== '') ? $ctxScope : $this->scopeOfSlug($slug);

        if ($scope === '' && $slug !== \Gov2lib\fileSource::tenant()) {
            $res['notice'] = 'Scope portal tidak dikenali registry; konteks tidak diubah';

            return;
        }

        $unit = self::normSlug($row['portal'] ?? '');
        if ($unit === '') {
            $res['notice'] = 'Unit belum punya slug portal; konteks diset ke induk portal';
        }

        try {
            \Gov2lib\context::set($self->ses->val, $scope, $slug, $unit, $unit !== '' ? $unitId : 0);
        } catch (\Throwable $e) {
            error_log('gov2option: gagal set konteks unit pusat (' . $e->getMessage() . ')');
        }
    }

    function dependencies () {
        global $self;
        $self->take("dpdraft2","draft");
    }

    function getRolePrivilege($id) {
        global $self, $uri;
        $query = "SELECT role FROM member WHERE id=%i LIMIT 1";
        try {
            $results['role'] = \DB::queryFirstField($query, $id); 
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage().":".$uri);
        }

        $query2 = "SELECT id,member_id,kecamatan_id FROM privilege WHERE member_id=%i";
        try {
            $results['privilege'] = \DB::query($query2, $id); 
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage().":".$uri);
        }
        
		return $results;
    }
}