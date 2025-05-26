<?php namespace Gov2lib;

use DB;
use Exception;
use MeekroDBException;
use WhereClause;
use \Gov2lib\DBConnector;
/**
 * -----------------------------------
 * Provide an API for gov2option app.
 *
 * Class gov2option
 * @package Gov2lib
 * -----------------------------------
 */
class gov2option
{
    function __construct ()
    {
        global $doc, $config;
        try {
            $cookies = $doc->envRead($_COOKIE['Gov2Session']);
            $this->dsn = $cookies['portal'];
            if (!$this->dsn) {
                $this->dsn = $config->domain->attr['dsn'];
            }
        } catch (Exception $e) {
            $this->dsn = $config->domain->attr['dsn'];
        }
    }

    /**
     * Get a single row from options table.
     *
     * @param array $where
     * @param string $whereType
     * @param string[] $select
     * @return null|array
     */
    function get ($where=[], $whereType='and', $select=['id', 'parent_id', 'nama', 'value'])
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

    function connector_get ($where=[], $whereType='and', $select=['id', 'parent_id', 'nama', 'value'])
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

    function create ($data)
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

    function mvc ()
    {
        return new MVC();
    }

    /**
     * Get gov2option active year
     * 
     * @return int year or current year by default if no year options found.
     */
    public function getActiveYear()
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

        if(!empty($member)){
            if(!empty($member['attr'])){
                $xmlArray = json_decode(json_encode(simplexml_load_string($member['attr'])), true);
                if($xmlArray['tahun']){
                    $activeYear = $xmlArray['tahun'];
                }
            }
        }

        return $activeYear;
    }

    function getMember(){
        global $doc, $self;

        $accountId = $self->ses->val['account_id'];

        $_response = null;
        try {
            $_query ="SELECT * FROM member WHERE account_id=%i";
            $_response = \DB::queryFirstRow($_query, $accountId);
        } catch (\MeekroDBException $DBException) {
            $this->exceptionHandler($DBException->getMessage());
        }
        return $_response;
    }
}

class MVC
{
    function __construct($className = "")
    {
        global $self;
        $this->id = 0;
        $this->parent_id = 0;
        $this->name = $className ?: $self->className;
        $this->active = true;
        $this->recorded = false;

        $this->get();
    }

    final function get ()
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

    private function create ()
    {
        global $self, $doc, $pageID;
        $parent_data = ['app' => $pageID, 'nama' => 'MVC', 'parent_id' => 0,
                        'status' => 'on', 'type' => 'option', 'level' => 1,
                        'level_label' => 'cluster', 'privilege' => 'admin',
                        'keterangan' => 'Enable/Disable MVC', 'created_by' => $self->ses->val['account_id']];
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
           return  $mvc;
        }
    }
}