<?php namespace App\gov2login\model;

class privilege extends \Gov2lib\crudHandler {
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
        global $config,$pageID;
        $GLOBALS['vueData']['scrollInterval']=$_scrollInterval;
        $GLOBALS['vueData']['interval']=array(100,200,300);

        //---gov2pagination
        $GLOBALS['vueData']['pathurl']=$config->webroot."/components/gov2nav/breadcrumb/$pageID/".$this->className."/menu";
        $GLOBALS['vueData']['kabid']=$config->domain->attr['id'];
        $GLOBALS['vueCreated'].='eventBus.$on("refreshTag", this.refreshTag);';
        $GLOBALS['vueMethods'].='refreshTag: function(data) {            
			eventBus.$emit("refreshDatawilayah",data);
		},';

        $instances=[
            'wilayah',
            'member'
        ];

        foreach($instances as $instance){
            $GLOBALS['vueData']['searchQuery'.$instance]='';
            $GLOBALS['vueCreated'].='eventBus.$on("searchQuery'.$instance.'", this.setQuery'.$instance.');';
            $GLOBALS['vueMethods'].='setQuery'.$instance.': function(data) {this.searchQuery'.$instance.'=data;},';

            $GLOBALS['vueData']['scrolls'.$instance]=1;
            $GLOBALS['vueCreated'].='eventBus.$on("setScrolls'.$instance.'", this.setScrolls'.$instance.');';
            $GLOBALS['vueMethods'].='setScrolls'.$instance.': function(data) {this.scrolls'.$instance.'=data;},';

            // this is itemPerPage section
            $GLOBALS['vueData']['itemPerPage'.$instance]=5;
            $GLOBALS['vueCreated'].='eventBus.$on("setItemPerPage'.$instance.'", this.setItemPerPage'.$instance.');';
            $GLOBALS['vueMethods'].='setItemPerPage'.$instance.': function(data) {this.itemPerPage'.$instance.'=data},';

        }

	}
    
    function memberBrowse ($scroll) {
        global $uri, $config;
        try {
            $scrolled=$this->scroll($scroll);
            $query="SELECT * FROM {$this->tbl->target} WHERE role=%s LIMIT $scrolled";
            $results = \DB::query($query,'member');
            // var_dump($config->domain->attr['dsn']);
            // var_dump(\DB::query('select database()'));
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage().":".$uri);
		}
	    return $results;
	}
}
?>