<?php namespace App\survey\model;


class index extends \Gov2lib\crudHandler {
    function __construct () {
        global $config, $doc;
        $this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $this->controller=__DIR__."/../".$this->className.".php";
        try {
            $cookies = $doc->envRead($_COOKIE['Gov2Session']);
            $this->dsn = $cookies['portal'];
            $this->dsn_id = $cookies['portal_id'];
            if (!$this->dsn) {
                $this->dsn      = $config->domain->attr['dsn'];
                $this->dsn_id   = $config->domain->attr['id'];
            }
        } catch (\Exception $e) {
            $this->dsn      = $config->domain->attr['dsn'];
            $this->dsn_id   = $config->domain->attr['id'];
        }
        parent::__construct($this->dsn);
    }

    function dependencies () {
        global $self;
//        $self->take("dpdraft2","draft");
    }

    function getKlBkn($id=0){
        global $doc;
        $query = "SELECT * FROM ".$this->tbl->kementerian." WHERE id=%i";
        try {
            $_response = \DB::queryFirstRow($query, $id);
        } catch (\MeekroDBException $DBException) {
            $this->exceptionHandler($DBException->getMessage());
        }
        return $_response;
    }
}