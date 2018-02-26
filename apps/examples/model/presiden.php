<?php namespace App\examples\model;

class presiden extends \Gov2lib\crudHandler {
	function __construct () {
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $this->body("className",$this->className);
        parent::__construct();  
        $this->tbl->table=$this->tbl->presiden;
	}
    
    function loadTable ($_scrollInterval=1000) {
        //---gov2pagination
        $GLOBALS['vueData']['itemPerPage']=10;
        $GLOBALS['vueData']['scrollInterval']=$_scrollInterval;
        
        //---gov2formfield
        $GLOBALS['vueData']['readOnly']=true;
        $GLOBALS['vueData']['fieldurl']=$this->className.'/fields'; //<-overwrite default
	}
}
?>