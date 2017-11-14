<?php namespace Gov2lib\helper;
/********************************************************************
*	Date		: Monday, Okt 28, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*	Version		: 1.0.0 
*********************************************************************/

class vue extends checkExist {
	function __construct () {
		global $vars;
        $_app=$this->checkAppDir($vars["app"]);
        $this->baseName=$_app;
        $this->baseBody='bulmaJsBody.html';
        #---perlu antisipasi jika $_app null
        $this->controller=__DIR__."/index.php";
        if (!isset($vars["component"])) {$vars["component"]="";}
        $this->templateDir=__DIR__."/../../app/".$_app."/vue";
        $_component=$this->checkAppFile($_app."/vue",$vars["component"]);
        $this->componentName=$_component;
	}
}
?>