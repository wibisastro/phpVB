<?php namespace App\components\model;

class gov2notification extends \Gov2lib\document {
	function __construct () {
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
		$this->controller=__DIR__."/../".$this->className.".php";
	}
    
    function demo () {
        $GLOBALS['vueCreated'].='eventBus.$on("toggleClick", this.demoNotif);';
        $GLOBALS['vueMethods'].='demoNotif: function(data) { 
        var response=[];
        response["class"]="is-info";
        response["notification"]="Test Notification";
        eventBus.$emit("openNotif",response);},';        
    }
	
}
?>