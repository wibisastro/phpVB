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
        $q = "SELECT DISTINCT(app) FROM {$this->tbl->options} WHERE level=1 AND type='option' AND status='ON'";
        $qs = "SELECT DISTINCT(app) FROM {$this->tbl->options} WHERE level=1 AND type='service' AND status='ON'";
        $res = [];

        try {
            $res['options'] = \DB::query($q);
            $res['services'] = \DB::query($qs);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }
        return $res;
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