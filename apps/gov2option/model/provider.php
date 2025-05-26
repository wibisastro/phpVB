<?php namespace App\gov2option\model;

use Gov2lib\DBConnector;
use MeekroDBException;

class provider extends \Gov2lib\crudHandler
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

    function getMVCs($dsn, $parent_id=0)
    {
        $result = [];
        if (!$dsn){return "noDsnException: Invalid DSN name";}
        $connector = new DBConnector($dsn);

        if (!$parent_id) {
            $q = "SELECT * FROM {$this->tbl->table} WHERE nama='MVC'";
        } else {
            $q = "SELECT * FROM {$this->tbl->table} WHERE parent_id=%i";
        }
        try {
            $MVCs = $connector->db->query($q, $parent_id);
            $result = $MVCs;
        } catch (MeekroDBException $e) {
            $this->exceptionHandler("Exception at \"provider->getMVCs()\" : ".$e->getMessage());
        }
        return $result;
    }
}