<?php namespace App\components\model;

class gov2button extends \Gov2lib\document {
	function __construct () {
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
		$this->controller=__DIR__."/../".$this->className.".php";
        $GLOBALS['vueData']['isPressed']=false;
        $GLOBALS['vueCreated'].='eventBus.$on("toggleClick", this.toggleClick);';
        $GLOBALS['vueMethods'].='toggleClick: function(data) {this.isPressed=data;},';
	}
}
?>