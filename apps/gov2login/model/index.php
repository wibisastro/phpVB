<?php namespace App\gov2login\model;

class index extends \Gov2lib\crudHandler {
    function __construct () {
        global $doc,$config;
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $doc->body("className",$this->className);
        parent::__construct($config->domain->attr['dsn']);  
        $this->tbl->table=$this->tbl->member;
        $this->tbl->wilayah=$this->tbl->wilayah;
    }
}
?>
