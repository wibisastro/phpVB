<?php namespace Gov2lib\helper;
/********************************************************************
*	Date		: Monday, Okt 23, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*	Version		: 1.0.0 
*********************************************************************/

class css  extends checkExist {
	function __construct () {
		global $vars;
        $_app=$this->checkAppDir($vars["app"]);
        $this->baseName=$_app;
        $this->baseBody='bootstrapCssBody.html';
        #---perlu antisipasi jika $_app null
        $this->controller=__DIR__."/index.php";
        if (!isset($vars["style"])) {$vars["style"]="";}
        $this->templateDir=__DIR__."/../../app/".$_app."/css";
        $_style=$this->checkAppFile($_app."/css",$vars["style"]);
        $this->componentName=$_style;
        header('Content-Type: text/css');
	}
}
?>