<?php namespace App\gov2login\model;

class webmaster extends \Gov2lib\crudHandler {
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
    
    function loadTable ($_scrollInterval) {
        //---gov2pagination
        $GLOBALS['vueData']['itemPerPage']=10;
        $GLOBALS['vueData']['scrollInterval']=$_scrollInterval;
        
        //---gov2formfield
        $GLOBALS['vueData']['readOnly']=false;
        $GLOBALS['vueData']['fieldurl']=$this->className.'/fields'; //<-overwrite default
	}
    
    function memberBrowse ($scroll) {
        global $uri;
        try {
            $scrolled=$this->scroll($scroll);
            $query="SELECT * FROM ".$this->tbl->table." WHERE %s LIMIT $scrolled";
            $results = \DB::query($query,$this->memberWhere());
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage().":".$uri);
		}
	return $results;
	}
    
    function memberCount () {
		$query="SELECT count(id) as totalRecord FROM ".$this->tbl->table." WHERE %s";	
	   return \DB::queryFirstRow($query,$this->memberWhere());
	}
    
    function memberWhere () {
        $_where = new \WhereClause('and');
        $_where->add('role!=%s', 'guest');
        $_where->add('role!=%s', 'member');
        $_where->add('role!=%s', 'developer');
    return $_where;
    }
}
?>