<?php namespace Gov2lib;
/********************************************************************
*	Date		: Monday, Okt 23, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*	Version		: 1.0.0 
*********************************************************************/

class js extends checkExist {
	function __construct () {
		global $vars;
        $_app=$this->checkAppDir($vars["app"]);
        $this->baseName=$_app;
        $this->baseBody='bulmaJsBody.html';
        #---perlu antisipasi jika $_app null
        $this->controller=__DIR__."/index.php";
        if (!isset($vars["component"])) {$vars["component"]="";}
        $this->templateDir=__DIR__."/../../apps/".$_app."/js";
        $_component=$this->checkAppFile($_app."/js",$vars["component"]);
        $this->componentName=$_component;
        header('Content-Type: application/javascript');
	}
}
?>