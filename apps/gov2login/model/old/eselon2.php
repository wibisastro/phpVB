<?php namespace App\gov2login\model;

class eselon2 extends \Gov2lib\crudHandler {
	function __construct ($dsn) {
        global $doc,$config;
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $doc->body("className",$this->className);
        if (!$dsn)
        {
            $dsn = $config->domain->attr['dsn'];
        }
        parent::__construct($dsn);
        $this->tbl->table=$this->tbl->privilege;
        $this->tbl->source=$this->tbl->renstra;
        $this->tbl->target=$this->tbl->member;
	}
    
    function loadTable ($_scrollInterval) {
        global $config,$pageID;
        //---gov2pagination
        $GLOBALS['vueData']['pathurl']=$config->webroot."/components/gov2nav/breadcrumb/$pageID/".$this->className."/menu";
    
        $GLOBALS['vueCreated'].='eventBus.$on("refreshTag", this.refreshTag);';
        $GLOBALS['vueMethods'].='refreshTag: function(data) {            
            eventBus.$emit("refreshDataprogram",data);
        },';
	}
    
    function memberBrowse ($scroll) {
        global $uri, $config;
        try {
            $scrolled=$this->scroll($scroll);
            $query="SELECT * FROM ".$this->tbl->target." WHERE role=%s LIMIT $scrolled";
            $results = \DB::query($query,'member');
//            var_dump($config->domain->attr['dsn']);
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage().":".$uri);
		}
	    return $results;
	}
}
?>
