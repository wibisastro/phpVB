<?php

namespace App\home\model;

use DB;

class crud extends \Gov2lib\crudHandler
{
    public $tbl;

    function __construct ()
    {
        global $config;
        $this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $this->controller=__DIR__."/../".$this->className.".php";
        $this->tbl = new \stdClass();
        parent::__construct(trim($config->domain->attr['dsn']));
        $this->tbl->table= "daftar_aset";
    }

    function loadTable (): void
    {
        //---gov2pagination
        $GLOBALS['vueData']['geturl']='/home/crud';
        $GLOBALS['vueData']['itemPerPage'] = 50;
        $GLOBALS['vueData']['interval'] = array(50, 100, 200, 300);
        $GLOBALS['vueData']['scrollInterval'] = $this->scrollInterval;
        //---gov2formfield
        $GLOBALS['vueData']['fieldurl'] = $this->className.'/fields'; //<-overwrite default
    }

    function getRecords($scrolls,$data="") {
        global $uri,$config,$self;

        $scrolled=$this->scroll($scrolls);
        try {
            $_query ="SELECT id,nama,spesifikasi,kategori,
                            IF (kategori='tb','Tak Berwujud',
                            CONCAT(UPPER(SUBSTRING(`kategori`, 1, 1)), LOWER(SUBSTRING(`kategori`, 2)))) AS nama_kategori,
                            jumlah FROM {$this->tbl->table} LIMIT $scrolled";
            $_response = DB::query($_query);
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage().":".$uri);
		}
	    return $_response;
    }

}