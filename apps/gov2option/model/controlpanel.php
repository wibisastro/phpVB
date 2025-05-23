<?php namespace App\gov2option\model;

class controlpanel extends \Gov2lib\crudHandler {
	function __construct () {
        global $doc,$config;
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $doc->body("className",$this->className);
        parent::__construct($config->domain->attr['dsn']);
        $this->tbl->table=$this->tbl->options;
	}
    
    function loadTable ($_scrollInterval) {
        global $config,$pageID;
        //---gov2pagination
        $GLOBALS['vueData']['pathurl']=$config->webroot."/components/gov2nav/breadcrumb/$pageID/".$this->className."/menu";
        $GLOBALS['vueData']['kabid']=$config->domain->attr['id'];
        $GLOBALS['vueData']['itemPerPage']=100;
        $GLOBALS['vueCreated'].='eventBus.$on("refreshTag", this.refreshTag);';
        $GLOBALS['vueMethods'].='refreshTag: function(data) {            
			eventBus.$emit("refreshDatawilayah",data);
		},';
	}

	function getUnits ()
    {
        $result = [];
        $q = "SELECT a.id, a.nama, a.portal, a.level,  
                IF(a.level = 3, CONCAT(b.kode, '.', a.kode), a.kode) AS kode 
                FROM {$this->tbl->kementerian} a
                LEFT JOIN {$this->tbl->kementerian} b ON b.id=a.parent_id
                WHERE a.level > 1 ORDER BY kode
                ";

        try {
            $result = \DB::query($q);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler("Exception at controlpanel->getCluster() : " . $e->getMessage());
        }
        return $result;
    }
}