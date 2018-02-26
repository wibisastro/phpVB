<?php namespace App\components\model;

class gov2pagination extends \Gov2lib\document {
	function __construct () {
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
		$this->controller=__DIR__."/../".$this->className.".php";
        $GLOBALS['vueData']['scrollInterval']=4;
        $GLOBALS['vueData']['interval']=array(1,2,5,10);
        $GLOBALS['vueCreated'].='eventBus.$on("pagination", this.pagination);';
        $GLOBALS['vueMethods'].='pagination: function(data) {this.records=data["records"];this.itemPerPage=data["itemPerPage"];},';
	}
    
    function demo () {
        $GLOBALS['vueData']['records']=100;
        $GLOBALS['vueData']['itemPerPage']=10;
    }
	
}
?>