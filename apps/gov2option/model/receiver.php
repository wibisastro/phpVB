<?php namespace App\gov2option\model;

use Gov2lib\DBConnector;
use MeekroDBException;

class receiver extends \Gov2lib\crudHandler
{
    function __construct () {
        global $doc,$config;
        $this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $doc->body("className",$this->className);
        parent::__construct($config->domain->attr['dsn']);
        $this->tbl->table=$this->tbl->options;
    }

    /**
     * @param $data
     * @return int
     */
    function token_option_save($data)
    {
        $isAssoc = $this->isAssoc($data);
        try {
            \DB::insert($this->tbl->table, $data);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }
        return $isAssoc ? \DB::insertId() : \DB::affectedRows();
    }

    function isAssoc($arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    function is_token_exist($token)
    {
        $q = "SELECT 1 AS exist FROM {$this->tbl->table} WHERE value=%s";
        $result = null;
        try {
            $qr = \DB::queryFirstRow($q, $token);
            if ($qr) {
                $result = $qr['exist'];
            }
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }
        return $result;
    }

    function update_counter($token)
    {
        $q = "UPDATE LOW_PRIORITY options a 
                LEFT JOIN options b ON b.nama='token' AND b.value=%s 
                SET a.value=CAST(a.value AS UNSIGNED)+1 
                WHERE a.nama='counter' AND a.parent_id=b.parent_id";
        $affected = 0;
        try {
            \DB::query($q, $token);
            $affected = \DB::affectedRows();
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }
        return $affected;
    }

    function setMVCs($dsn, $data)
    {
        if (!$dsn){return "noDsnException: Invalid DSN name";}
        $connector = new DBConnector($dsn);

        foreach ($data AS $i => $row) {
            $update_data = array('value' => intval($row['value']) == 1 ? 1 : "");
            try {
                $connector->db->update($this->tbl->table, $update_data, 'id=%i', $row['id']);
                $affected = $connector->db->affectedRows();
                $data[$i]['updated'] = $affected;
            } catch (MeekroDBException $e) {
                $data[$i]['updated_message'] = $e->getMessage();
            }
        }
        return $data;
    }
}