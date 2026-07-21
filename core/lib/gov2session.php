<?php

namespace Gov2lib;

use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Gov2lib\Enums\UserRole;
use Gov2lib\Enums\PagePrivilege;

/*
Author		: Wibisono Sastrodiwiryo
Date		: 21 Dec 2017
Copyleft	: eGov Lab UI
Contact		: wibi@alumni.ui.ac.id
Version		: 0.0.1
Version		: 0.0.2 nambah error message role 6 Aug 2020
Version		: 0.0.3 nambah propagasi superadmin per wilayah 10 Aug 2020
Version		: 0.0.4 05 April 2021, [rijal@cybergl.co.id] fix bug salah query field, yg tadinya ke field account_id menjadi ke field id
Version		: 0.0.5 06 Mei 2021, [rijal@cybergl.co.id] ganti superadmin menjadi superuser dan tambah fungsi share xml
Version		: 0.0.6 26 Mei 2021, [rijal@cybergl.co.id] ubah str_replage menjadi preg_replace
Version		: 0.1.0 28 Feb 2026, [claude] extract handleAuthorization() dari authenticate()
*/
class gov2session extends dsnSource
{
    public \GuzzleHttp\Client $client;
    public int $timeout = 0;
    public array $val = [];
    /**
     * Initialize session with optional authentication data
     */
    public function __construct(string|array $_auth = "")
    {
        parent::__construct();
        $this->client = new \GuzzleHttp\Client();
        $this->timeout = time() + (24 * 60 * 60);

        // Dump debug ?view=session/cookie HANYA stage local/dev (#6161): di prod
        // ini membocorkan isi sesi (account_id, daftar client SSO) ke siapa pun.
        if (isset($_GET['view']) && defined('STAGE') && (STAGE == 'local' || STAGE == 'dev')) {
            echo match($_GET['view']) {
                'cases' => json_encode($cases ?? []),
                'cookie' => json_encode($_COOKIE),
                'session' => json_encode($_SESSION),
                default => '',
            };
            exit;
        }

        if ($_COOKIE['Gov2Session'] ?? false) {
            // Session store selalu MySQL master (tidak ikut tier per-app) —
            // muat MeekroDB begitu ada sesi login (T4 #6085)
            self::requireMeekroDB();
            $this->sesRead($_COOKIE['Gov2Session']);
            global $doc;
            if (isset($doc) && !empty($this->val)) {
                $doc->body['_SESSION'] = $this->val;
            }
        } else {
            if (($_auth['cmd'] ?? null) !== 'sessave') {
                $_token['userRole'] = "public";
            }
        }
    }

    /**
     * Reset the current session
     */
    public function sesReset(): void
    {
        unset($_COOKIE['Gov2Session']);
        setcookie("Gov2Session", "", time() - 3600, "/");
    }

    /**
     * Save session data with JWT encoding
     */
    public function sesSave(array $data, int $redirect = 0): void
    {
        global $publickey;
        if ($_COOKIE['Gov2Session'] ?? false) {
            $_token = $_COOKIE['Gov2Session'];
        } else {
            $_data['userRole'] = "public";
            $_token = JWT::encode($_data, $publickey, 'HS256');
        }

        $_existing = JWT::decode($_token, new Key($publickey, 'HS256'));
        $_data = array_merge((array)$_existing, (array)$data);
        $_token = JWT::encode($_data, $publickey, 'HS256');

        setcookie("Gov2Session", $_token, $this->timeout, "/");
        if ($redirect) {
            header('location: /');
            exit;
        }
    }

    /**
     * Read and decode JWT session data
     */
    public function sesRead(string $data): void
    {
        global $publickey;
        $_result = JWT::decode($data, new Key($publickey, 'HS256'));
        $this->val = json_decode(json_encode($_result), true);
    }

    /**
     * Get role level mapping from database table
     *
     * R1 role-framework: JANGAN dipakai untuk hierarki role user — sumber
     * otoritatifnya enum UserRole. Sisa pemakaian sah tinggal metadata
     * struktur non-role (privilegeRead: level_label tabel wilayah dsb).
     */
    public function getRoleLevel(string $table, string $levelName): array
    {
        $_member = \DB::query("DESCRIBE $table");
        $patterns = ["/^enum\(/x", "/'/x", "/\)/x"];
        $_result = [];

        foreach ($_member as $_column) {
            if ($_column['Field'] == $levelName) {
                $_result = preg_replace($patterns, "", $_column['Type']);
                $_result = explode(",", $_result);
                break;
            }
        }

        foreach ($_result as $_key => $_val) {
            $_reorder[$_key + 1] = $_val;
        }

        $_result = array_flip($_reorder);
        return $_result;
    }

    /**
     * Authenticate user and check authorization
     */
    public function authenticate(string $_privilege = "member", string $_maintenance = ""): void
    {
        global $pageID, $doc, $config;

        try {
            if ($_privilege == "Selesai") {
                throw new \Exception("Selesai: Semua fungsi ditutup karena proses kerja telah selesai");
            } elseif (STAGE != 'local') {
                if (($config->domain->attr['shift'] ?? false)
                    && $config->domain->attr['shift'] != date("A")
                    && $config->domain->attr['shift'] != 'ALL') {
                    throw new \Exception("WrongTime:Portal ini hanya dapat dibuka pada waktu " . $config->domain->attr['shift']);
                } elseif (!isset($this->val['account_id']) && $_privilege != "public") {
                    throw new \Exception("NotLogin:Halaman " . strtoupper($pageID) . " harus login terlebih dahulu");
                } else {
                    $doc->body['_SESSION'] = (array)$this->val;
                    if (!($this->val['id'] ?? false) && $_privilege != 'public') {
                        $_member = $this->memberRead($this->val['account_id']);
                        if ($_member['id'] ?? false) {
                            $this->val['id'] = $_member['id'];
                            $this->val['userRole'] = $_member['role'];
                            $this->val['status'] = $_member['status'];
                            $this->val['created_at'] = $_member['created_at'];
                            $this->val['lastlogin_at'] = $_member['lastlogin_at'];
                            $this->val['counter'] = $_member['counter'];
                            if ($pageID != "gov2login") {
                                $this->val['pageID'] = $pageID;
                            }
                            $this->sesSave($this->val);
                            $this->memberUpdateCounter($this->val['id']);
                            match ($this->val['status']) {
                                'pending' => throw new \Exception("Pending:Akun Anda belum aktif, silahkan aktivasi terlebih dahulu"),
                                'suspended' => throw new \Exception("Suspended:Akun Anda terblokir, silakan hubungi Admin"),
                                default => $this->handleAuthorization($pageID, $doc, $config, $_privilege, $_maintenance),
                            };
                        }
                    } else {
                        if ($_privilege != 'public' && (isset($this->val['account_id']) && !isset($this->val['id']))) {
                            $_member = $this->memberRead($this->val['account_id']);
                            if ($_member['id'] ?? false) {
                                $this->val['id'] = $_member['id'];
                                $this->val['userRole'] = $_member['role'];
                                $this->val['status'] = $_member['status'];
                                $this->val['created_at'] = $_member['created_at'];
                                $this->val['lastlogin_at'] = $_member['lastlogin_at'];
                                $this->val['counter'] = $_member['counter'];
                                if ($pageID != "gov2login") {
                                    $this->val['pageID'] = $pageID;
                                }
                                $this->sesSave($this->val);
                            }
                        }

                        if ($this->val['id'] ?? false) {
                            $doc->body['_SESSION["userRole"]'] = $this->val['userRole'];
                            if ($_privilege != 'public') {
                                $this->memberUpdateCounter($this->val['id']);

                                match ($this->val['status']) {
                                    'pending' => throw new \Exception("Pending:Akun Anda belum aktif, silahkan aktivasi terlebih dahulu"),
                                    'suspended' => throw new \Exception("Suspended:Akun Anda terblokir, silakan hubungi Admin"),
                                    default => $this->handleAuthorization($pageID, $doc, $config, $_privilege, $_maintenance),
                                };
                            }
                        }
                    }
                }
            } else {
                $doc->body("_SESSION['fullname']", 'Development');
                $doc->body("_SESSION['account_id']']", '-1');
            }
            // Re-push _SESSION template: val bisa berubah DI DALAM authenticate
            // (memberRead/checkSuperuser mengisi userRole dst) SETELAH doc->body
            // diisi konstruktor — tanpa ini menu ber-gate role baru tampil pada
            // request berikutnya (cookie), bukan halaman ini.
            if (!empty($this->val)) {
                $doc->body['_SESSION'] = (array)$this->val;
            }
        } catch (\Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }
    }

    /**
     * Handle authorization checks for user roles
     *
     * R1 role-framework: SATU sumber hierarki. Level user dibaca dari enum
     * UserRole (otoritatif) — bukan lagi DESCRIBE tabel member; peta privilege
     * halaman berbasis PagePrivilege::defaultMap() dari enum yang sama — bukan
     * lagi angka XML. XML tinggal override sadar (deprecated, ber-log).
     * #---coded by claude (extracted from authenticate(), logic original by wibi)
     */
    private function handleAuthorization(string $pageID, object $doc, object $config, string $_privilege, string $_maintenance = ""): void
    {
        // Peta privilege = MERGE tiga lapis DI ATAS peta kanonik enum:
        //   PagePrivilege::defaultMap() → <pageroles> config server → pageroles.xml per-app
        // - defaultMap() di KODE = sumber selalu-ada (config.{stage}.xml adalah
        //   config INSTANCE per-server; config.prod.xml repo tak punya
        //   <pageroles> — tanpa basis kode, fail-closed mengunci fleet).
        // - Dua lapis XML = override sadar per-server / per-app (mis. gov2login
        //   member=3). Deprecated sejak R1: tiap penyimpangan dari kanonik
        //   di-log sebagai penanda migrasi, hasil EFEKTIF-nya dipertahankan.
        $_customPageroles = $this->readXML($pageID, "pageroles");
        $_defaultMap = PagePrivilege::defaultMap();
        $_pageRole = array_merge(
            $_defaultMap,
            (array)$config->pageroles,
            $_customPageroles ? (array)$_customPageroles : []
        );

        // Log peringatan (sekali per app per proses) bila lapisan XML MENGUBAH
        // level kanonik atau menambah nama non-kanonik (mis. 'default').
        static $_warnedApps = [];
        if (!isset($_warnedApps[$pageID])) {
            $_warnedApps[$pageID] = true;
            $_diff = [];
            foreach ($_pageRole as $_name => $_level) {
                if (!isset($_defaultMap[$_name]) || (int)$_level !== $_defaultMap[$_name]) {
                    $_diff[] = $_name . '=' . (int)$_level;
                }
            }
            if ($_diff) {
                error_log("gov2session: pageroles app '{$pageID}' menyimpang dari kanonik (" . implode(',', $_diff) . ") — angka XML deprecated sejak R1 role-framework, migrasikan ke nama privilege kanonik");
            }
        }

        // Fail-closed (R0): privilege tanpa level setelah merge = tolak. Dengan
        // peta kanonik dari enum, SEMUA nama role sah (public..developer,
        // termasuk 'owner' yang dulu tak terdefinisi) kini privilege valid;
        // yang ditolak tinggal nama asing ('sdi', typo). 'maintenance' sengaja
        // di luar peta (cabang khusus di bawah).
        // $_privilege bisa datang dari segmen URL (authenticate($vars['role']))
        // — bersihkan newline (anti log injection) + potong sebelum masuk log.
        $_privLog = substr(str_replace(["\r", "\n"], ' ', $_privilege), 0, 64);
        // Fail-closed juga utk level NON-NUMERIK (typo config, mis.
        // <webmaster>x</webmaster>): tanpa guard ini (int) cast menjadikannya
        // 0 = terbuka utk semua — kebalikan arah R0 yang kebetulan menolak.
        if ((!isset($_pageRole[$_privilege]) || !is_numeric((string)$_pageRole[$_privilege]))
            && $_privilege != PagePrivilege::MAINTENANCE->value) {
            error_log("gov2session: privilege '{$_privLog}' tak dikenal/level non-numerik utk app '{$pageID}' — ditolak fail-closed");
            throw new \Exception("UnknownPrivilege:Level akses '" . $_privilege . "' tidak terdefinisi, akses ditolak. Silakan hubungi Admin");
        }

        // maintenance: selalu menolak, tanpa perbandingan level (tak ada di peta).
        if ($_privilege == PagePrivilege::MAINTENANCE->value) {
            throw new \Exception("Maintenance:System sedang dalam peningkatan kapasitas hingga jam " . $_maintenance);
        }

        $_role = $this->checkSuperuser();
        if ($_role) {
            $this->val['userRole'] = $_role;
        }

        // Role tak dikenal (data lama / superuser.xml menyimpang / role domain
        // lain sebelum mapping R5) → fromName jatuh ke guest + log, bukan fatal.
        $_userLevel = UserRole::fromName((string)($this->val['userRole'] ?? ''))->level();
        $_requiredLevel = (int)$_pageRole[$_privilege];

        if ($_userLevel < $_requiredLevel) {
            throw new \Exception($_privilege == PagePrivilege::CLOSED->value
                ? "Closed:Menu ini ditutup"
                : "Unauthorized:UserRole akun Anda tidak memiliki wewenang mengakses halaman dengan PageRole " . strtoupper($_privilege) . ". Silakan hubungi Admin");
        }
    }

    /**
     * Update member login counter and timestamp
     */
    public function memberUpdateCounter(int $id): void
    {
        try {
            $_query = "UPDATE LOW_PRIORITY member SET counter=counter+1,lastlogin_at=NOW() WHERE id=%i";
            \DB::query($_query, $id);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }
    }

    /**
     * Read member data from database
     */
    public function memberRead(int|string $id = 0): ?array
    {
        global $pageID;
        $WHERE = is_string($id) ? "account_id=%s" : "account_id=%i";

        try {
            $this->connectDB('master');
            $_role = $this->checkSuperuser();
            $_query = "SELECT * FROM member WHERE {$WHERE}";
            $_result = \DB::queryFirstRow($_query, $id);

            if (!is_array($_result)) {
                $_id = $this->insertMember();
                $_query = "SELECT * FROM member WHERE id=%i";
                $_result = \DB::queryFirstRow($_query, $_id);
            }

            if ($_role) {
                $_result['role'] = $_role;
            }
        } catch (\MeekroDBException $e) {
            $msg = $e->getMessage();
            $connKeywords = ['Unable to connect to MySQL', 'Access denied', 'Connection refused', "Can't connect to"];
            foreach ($connKeywords as $keyword) {
                if (stripos($msg, $keyword) !== false) {
                    $stage = defined('STAGE') ? STAGE : 'dev';
                    $msg = "Koneksi database gagal ({$msg}). Periksa file apps/{$pageID}/xml/dsnSource.{$stage}.xml";
                    break;
                }
            }
            throw new \Exception('DatabaseError:' . $msg);
        }

        return $_result;
    }

    /**
     * Insert new member record
     */
    public function insertMember(): ?int
    {
        global $doc, $config, $pageID;
        $_role = UserRole::GUEST->value;

        try {
            $_customDataroles = $this->readXML($pageID, "dataroles");
            $_dataRole = $_customDataroles ? $_customDataroles : (array)$config->dataroles;

            $_level1 = $_dataRole["level"][0];
            $_level2 = $_dataRole["level"][1];

            if ($config->domain->attr['level'] == 0) {
                ${"_" . $_level1 . "_id"} = 0;
                ${"_" . $_level2 . "_id"} = 0;
            } elseif ($config->domain->attr['level'] == 1) {
                ${"_" . $_level2 . "_id"} = 0;
                ${"_" . $_level1 . "_id"} = (int)trim($config->domain->attr['id']);
            } else {
                ${"_" . $_level2 . "_id"} = (int)trim($config->domain->attr['id']);
                ${"_" . $_level1 . "_id"} = $this->parentRead(
                    trim($config->domain->attr['table']),
                    trim($config->domain->attr['id'])
                );
                ${"_" . $_level1 . "_id"} += 0;
            }

            $_fields = [
                'account_id' => $this->val['account_id'],
                'fullname' => $this->val['fullname'],
                'email' => $this->val['email'],
                'status' => "active",
                'role' => $_role,
                'counter' => "1",
                'lastlogin_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                $_level2 . '_id' => ${"_" . $_level2 . "_id"},
                $_level1 . '_id' => ${"_" . $_level1 . "_id"}
            ];

            \DB::insert("member", $_fields);
            return \DB::insertId();
        } catch (\MeekroDBException $e) {
            $doc->exceptionHandler($e->getMessage());
        } catch (\Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }

        return null;
    }

    /**
     * Check if user is a superuser
     */
    public function checkSuperuser(?string $newPageID = null): string|bool
    {
        global $pageID, $config;
        $_role = "";

        $getPageID = $newPageID ?? $pageID;
        $_superuser = $this->readXML($getPageID, "superuser");

        if (!$_superuser) {
            return false;
        }

        $_domainAttr = $config->domain->attr ?? [];
        if (($_domainAttr['level'] ?? null) == 2) {
            $_id = $this->parentRead(trim($_domainAttr['table'] ?? ''), trim($_domainAttr['id'] ?? ''));
        } else {
            $_id = trim($_domainAttr['id'] ?? '');
        }

        foreach ($_superuser->role as $_roles) {
            $_attr = $_roles->attributes();

            if (in_array($this->val['account_id'], (array)$_roles->account_id) && !($_attr['id'] ?? false)) {
                $_role = true;
            } elseif (in_array($this->val['account_id'], (array)$_roles->account_id) && ($_attr['id'] ?? 0) > 0 && $_id == $_attr['id']) {
                $_role = true;
            } else {
                $_role = false;
            }

            if ($_role == true) {
                $_role = trim(str_replace("'", "", stripslashes($_attr['name'])));
                break;
            }
        }

        return $_role;
    }

    /**
     * Read parent record from database table
     */
    public function parentRead(string $table = "", int $id = 0): ?int
    {
        global $doc;
        if (!$table) {
            $table = "wilayah_local";
        }

        $_query = "SELECT * FROM $table WHERE id=%i";
        try {
            $result = \DB::queryFirstRow($_query, $id);
        } catch (\MeekroDBException $e) {
            $doc->exceptionHandler($e->getMessage());
        }

        return $result['parent_id'] ?? null;
    }

    /**
     * Authorize user for specific structure and role
     */
    public function authorize(int $id, string $structure = "wilayah", string $role = "member", int $level = 3): void
    {
        global $pageID, $doc;

        if (!$structure) {
            $structure = "wilayah";
        }

        try {
            if (STAGE != 'local') {
                $_privilege = $this->privilegeRead($id, $structure, $level);

                if (!($this->val['privilege'] ?? false)) {
                    $this->val['privilege'] = $_privilege;
                    $this->sesSave($this->val);
                } elseif (($this->val['privilege'][$structure . '_id'] ?? null) != $id) {
                    unset($this->val['privilege']);
                    $this->val['privilege'] = $_privilege;
                    $this->sesSave($this->val);
                }

                if (($this->val['privilege']['authorisation'] ?? null) == 'authorized') {
                    $doc->body[$structure . '_penugasan'] = $this->val['privilege'][$structure . '_nama'];
                } elseif (
                    ($this->val['privilege']['authorisation'] ?? null) == 'unauthorized' &&
                    UserRole::fromName((string)($this->val['userRole'] ?? ''))->level() <= UserRole::fromName($role)->level()
                ) {
                    throw new \Exception("Unauthorized:Akun Anda tidak memiliki wewenang di " . ucfirst($this->val['privilege']['level_label']) . " " . $this->val['privilege']['nama'] . ", silakan hubungi Admin");
                }
            } else {
                $this->val['privilege']['authorisation'] = 'authorized';
                $this->val['privilege'][$structure . '_id'] = $id;
                $this->sesSave($this->val);
            }
        } catch (\Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }
    }

    /**
     * Read privilege data for member
     */
    public function privilegeRead(int $id, string $structure, int $level = 3): ?array
    {
        global $doc, $config, $self;
        try {
            $_query = "SELECT * FROM $structure WHERE id=%i";
            $_structure = \DB::queryFirstRow($_query, $id);

            $_levelRole = array_flip($this->getRoleLevel($structure, 'level_label'));

            $_id = $_structure[$_levelRole[$level] . "_id"];
            if ($_id > 0) {
                $_query = "SELECT * FROM privilege WHERE member_id=%i AND ";
                $_query .= $_levelRole[$level] . "_id=%i";

                $_result = \DB::queryFirstRow($_query, $self->ses->val['id'], $_id);
                if ($_result['id'] ?? false) {
                    $_result['authorisation'] = 'authorized';
                } else {
                    $_result = $_structure;
                    $_result['authorisation'] = 'unauthorized';
                }
            } else {
                $_result = $_structure;
                $_result['authorisation'] = 'authorized';
            }
        } catch (\MeekroDBException $e) {
            $doc->exceptionHandler($e->getMessage());
        }

        return $_result;
    }

    /**
     * Read XML configuration file
     */
    public function readXML(string $pageID, string $file): ?object
    {
        global $doc;
        $_data = null;

        $_pageID = $this->val['pageID'] ?? $pageID;
        $_filePath = __DIR__ . "/../../apps/$_pageID/xml/$file.xml";

        if (file_exists($_filePath)) {
            $_data = simplexml_load_file($_filePath, "SimpleXMLElement", LIBXML_NOCDATA);

            if (is_object($_data)) {
                if ($_data->share ?? false) {
                    // R0 role-framework: share rusak (app target sudah tak ada, mis.
                    // rokuone/sdi) dulu fatal SuperUserShareFileNotExist dan mematikan
                    // seluruh halaman ber-gate app itu. Kini log + abaikan file:
                    // return null = seolah tak ada file custom (superuser → tidak ada
                    // superuser; pageroles/dataroles → fallback config default).
                    $shared_file = __DIR__ . "/../../apps/{$_data->share}/xml/{$file}.xml";
                    if (file_exists($shared_file)) {
                        $shared_file_list = simplexml_load_file(
                            $shared_file,
                            "SimpleXMLElement",
                            LIBXML_NOCDATA
                        );
                        if (is_object($shared_file_list)) {
                            $_data = $shared_file_list;
                        } else {
                            error_log("gov2session::readXML: share '{$_data->share}' utk {$file}.xml tidak valid ({$shared_file}) — file diabaikan");
                            return null;
                        }
                    } else {
                        error_log("gov2session::readXML: share '{$_data->share}' utk {$file}.xml tidak ditemukan ({$shared_file}) — file diabaikan");
                        return null;
                    }
                }
            }
        }

        return $_data;
    }
}
