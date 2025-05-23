<?php namespace App\gov2login\model;

class ref_user extends \Gov2lib\crudHandler {
	function __construct () {
        global $doc,$config;
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $doc->body("className",$this->className);
        parent::__construct($config->domain->attr['dsn']);
        $this->tbl->table=$this->tbl->member;
	}
    
    function loadTable ($_scrollInterval) {
        //---gov2pagination
        $GLOBALS['vueData']['itemPerPage']=10;
        $GLOBALS['vueData']['scrollInterval']=$_scrollInterval;
        
        //---gov2formfield
        $GLOBALS['vueData']['readOnly']=false;
        $GLOBALS['vueData']['fieldurl']=$this->className.'/fields'; //<-overwrite default
	}

    function getRecords ()
    {
        global $uri, $config;
        $result = [];
        $connector = new \Gov2lib\DBConnector($config->domain->attr['dsn']);
        $q = "SELECT * FROM {$this->tbl->table}";

        try {
            $result = $connector->db->query($q);
        } catch(\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }
}
?>