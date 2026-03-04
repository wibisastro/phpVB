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
    public mixed $dsn = null;
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
                $this->dsn = $config->domain->attr['dsn'];
            }
        } catch (Exception $e) {
            $this->dsn = $config->domain->attr['dsn'];
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
        $select_field = join(',', $select);
        $where_clause = new WhereClause($whereType);

        foreach ($where as $key => $val) {
            $kwarg = gettype($val) === 'string' ? "{$key}=%s" : "{$key}=%i";
            $where_clause->add($kwarg, $val);
        }

        $q = "SELECT {$select_field} FROM options WHERE %l";
        $res = null;

        try {
            $res = DB::queryFirstRow($q, $where_clause);
        } catch (MeekroDBException $e) {
            if ($e->getCode() == 0) {
                $connector = new DBConnector($this->dsn);
                $res = $connector->db->queryFirstRow($q, $where_clause);
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
        $select_field = join(',', $select);
        $connector = new DBConnector($config->domain->attr['dsn']);
        $where_clause = new WhereClause($whereType);

        foreach ($where as $key => $val) {
            $kwarg = gettype($val) === 'string' ? "{$key}=%s" : "{$key}=%i";
            $where_clause->add($kwarg, $val);
        }

        $q = "SELECT {$select_field} FROM options WHERE %l";
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
            DB::insert('options', $data);
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

        $q = "SELECT id, app, type, privilege, nama, keterangan, status, value FROM options WHERE app=%s AND level=1 AND status=%s ORDER BY id ASC";

        try {
            $res = DB::query($q, $app, $status);
        } catch (MeekroDBException $e) {
            if ($e->getCode() == 0) {
                $connector = new DBConnector($this->dsn);
                $res = $connector->db->query($q, $app, $status);
            }
        } catch (Exception $e) {
            // Silently fail - options are non-critical for page rendering
        }

        return $res ?: [];
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
