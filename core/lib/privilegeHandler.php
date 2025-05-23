<?php namespace Gov2lib;
/********************************************************************
*	Date		: Fri, Jan 31, 2020
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*	Version		: 1.0.0 
*********************************************************************/

class privilegeHandler extends checkExist {
	function __construct () {
        global $vars,$config;
        parent::__construct($config->domain->attr['dsn']);         
        $_app=$this->checkAppDir($vars["app"]);
        $this->templateDir=__DIR__."/../../apps/".$_app."/view";
		$this->controller=__DIR__."/privilege.php";
	}
}
?>