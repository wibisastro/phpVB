<?php

namespace Gov2lib;

use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Session management class for Gov2 authentication and authorization
 *
 * @author Wibisono Sastrodiwiryo
 * @version 0.0.6
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

        if (isset($_GET['view'])) {
            echo match($_GET['view']) {
                'cases' => json_encode($cases ?? []),
                'cookie' => json_encode($_COOKIE),
                'session' => json_encode($_SESSION),
                default => '',
            };
            exit;
        }

        if ($_COOKIE['Gov2Session'] ?? false) {
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
            } elseif (STAGE != 'dev') {
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
                            if ($pageID != "gov2login") {
                                $this->val['pageID'] = $pageID;
                            }
                            $this->sesSave($this->val);
                            $this->memberUpdateCounter($this->val['id']);
                            match ($this->val['status']) {
                                'pending' => throw new \Exception("Pending:Akun Anda belum aktif, silahkan aktivasi terlebih dahulu"),
                                'suspended' => throw new \Exception("Suspended:Akun Anda terblokir, silakan hubungi Admin"),
                                default => $this->handleAuthorization($pageID, $doc, $config, $_privilege),
                            };
                        }
                    } else {
                        if ((isset($this->val['account_id']) && !isset($this->val['id']))) {
                            $_member = $this->memberRead($this->val['account_id']);
                            if ($_member['id'] ?? false) {
                                $this->val['id'] = $_member['id'];
                                $this->val['userRole'] = $_member['role'];
                                $this->val['status'] = $_member['status'];
                                if ($pageID != "gov2login") {
                                    $this->val['pageID'] = $pageID;
                                }
                                $this->sesSave($this->val);
                            }
                        }

                        $doc->body['_SESSION["userRole"]'] = $this->val['userRole'];
                        $this->memberUpdateCounter($this->val['id']);

                        match ($this->val['status']) {
                            'pending' => throw new \Exception("Pending:Akun Anda belum aktif, silahkan aktivasi terlebih dahulu"),
                            'suspended' => throw new \Exception("Suspended:Akun Anda terblokir, silakan hubungi Admin"),
                            default => $this->handleAuthorization($pageID, $doc, $config, $_privilege),
                        };
                    }
                }
            } else {
                $doc->body("_SESSION['fullname']", 'Development');
                $doc->body("_SESSION['account_id']']", '-1');
            }
        } catch (\Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }
    }

    /**
     * Handle authorization checks for user roles
     */
    private function handleAuthorization(string $pageID, object $doc, object $config, string $_privilege): void
    {
        $_userRole = $this->getRoleLevel('member', 'role');
        $_customPageroles = $this->readXML($pageID, "pageroles");
        $_pageRole = $_customPageroles ? (array)$_customPageroles : (array)$config->pageroles;

        $_role = $this->checkSuperuser();
        if ($_role) {
            $this->val['userRole'] = $_role;
        }

        if ($_userRole[$this->val['userRole']] < $_pageRole[$_privilege] && $_privilege != 'closed' && $_privilege != 'maintenance') {
            throw new \Exception("Unauthorized:UserRole akun Anda tidak memiliki wewenang mengakses halaman dengan PageRole " . strtoupper($_privilege) . ". Silakan hubungi Admin");
        } elseif ($_userRole[$this->val['userRole']] < $_pageRole[$_privilege] && $_privilege == 'closed') {
            throw new \Exception("Closed:Menu ini ditutup");
        } elseif ($_privilege == 'maintenance') {
            throw new \Exception("Maintenance:System sedang dalam peningkatan kapasitas hingga jam " . $_maintenance);
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
        global $doc;
        $WHERE = is_string($id) ? "account_id=%s" : "account_id=%i";

        try {
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
            $doc->exceptionHandler($e->getMessage());
        }

        return $_result;
    }

    /**
     * Insert new member record
     */
    public function insertMember(): ?int
    {
        global $doc, $config, $pageID;
        $_role = "guest";

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

        if ($config->domain->attr['level'] == 2) {
            $_id = $this->parentRead(trim($config->domain->attr['table']), trim($config->domain->attr['id']));
        } else {
            $_id = trim($config->domain->attr['id'] ?? '');
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
            if (STAGE != 'dev') {
                $_userRole = $this->getRoleLevel('member', 'role');
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
                    $_userRole[$this->val['userRole']] <= $_userRole[$role]
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
                            throw new \Exception('InvalidSuperuserShareFile:' . $shared_file);
                        }
                    } else {
                        throw new \Exception('SuperUserShareFileNotExist:' . $shared_file);
                    }
                }
            }
        }

        return $_data;
    }
}
