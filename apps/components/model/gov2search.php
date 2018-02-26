<?php namespace App\components\model;

class gov2search extends \Gov2lib\document {
	function __construct () {
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
		$this->controller=__DIR__."/../".$this->className.".php";
        $GLOBALS['vueData']['searchQuery']='';
        $GLOBALS['vueCreated'].='eventBus.$on("searchQuery", this.setQuery);';
        $GLOBALS['vueMethods'].='setQuery: function(data) {this.searchQuery=data;},';
	}
	
}
?>