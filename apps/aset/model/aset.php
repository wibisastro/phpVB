<?php

namespace App\aset\model;

/**
 * Model CRUD "aset" (crudHandler) — tabel di-set di konstruktor (override dbTables).
 * SENGAJA TIDAK meng-override getRecords: pakai base crudHandler::getRecords →
 * doBrowse yang bercabang ke repo() saat driver=supabase (PostgREST), tak seperti
 * exemplar home/crud yang override dgn SQL MySQL mentah (MeekroDB, single-tier).
 */
class aset extends \Gov2lib\crudHandler
{
    public ?\stdClass $tbl = null;

    function __construct ()
    {
        global $config;
        $this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $this->controller=__DIR__."/../".$this->className.".php";
        $this->tbl = new \stdClass();
        parent::__construct(trim($config->domain->attr['dsn']));
        $this->tbl->table = "phpvb_demo_aset";
    }

    function loadTable (): void
    {
        //---gov2pagination
        $GLOBALS['vueData']['geturl']='/aset';
        $GLOBALS['vueData']['itemPerPage'] = 50;
        $GLOBALS['vueData']['interval'] = array(50, 100, 200, 300);
        $GLOBALS['vueData']['scrollInterval'] = $this->scrollInterval;
        //---gov2formfield
        $GLOBALS['vueData']['fieldurl'] = $this->className.'/fields';
    }

    function dependencies () {
    }
}
