<?php namespace App\gov2login\model;

class role extends \Gov2lib\crudHandler {
	function __construct () {
        global $doc,$config;
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $doc->body("className",$this->className);
        parent::__construct($config->domain->attr['dsn']);
        $this->tbl->table=$this->tbl->privilege;
        $this->tbl->source=$this->tbl->wilayah;
        $this->tbl->target=$this->tbl->member;
	}
    
    function loadTable ($_scrollInterval) {
        global $config,$pageID,$scriptID;
        //---gov2pagination
        $GLOBALS['vueData']['pathurl']=$config->webroot."/components/gov2nav/breadcrumb/$pageID/".$this->className."/menu";
    
        $GLOBALS['vueCreated'].='eventBus.$on("refreshTag", this.refreshTag);';
        $GLOBALS['vueMethods'].='refreshTag: function(data) {            
			eventBus.$emit("refreshData'.$scriptID.'",data);
		},';

	}
    
    function memberBrowse ($scroll) {
        global $uri;
        try {
            $scrolled=$this->scroll($scroll);
            $query="SELECT * FROM ".$this->tbl->target." WHERE role=%s LIMIT $scrolled";
            $results = \DB::query($query,'member');
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage().":".$uri);
		}
	return $results;
	}
}
?>