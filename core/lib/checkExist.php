<?php namespace Gov2lib;
/********************************************************************
*	Date		: Monday, Okt 28, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*	Version		: 1.0.0 
*********************************************************************/

class checkExist extends dsnSource {
	function __construct ($_dsn="") {
        parent::__construct(); 
        list($_link_id,$_name)=$this->connectDB($_dsn);
	}
    
    function checkAppDir ($_appDir) {
        $_dir=__DIR__."/../../apps";
        $_dirs = array_slice(scandir($_dir), 2);
        foreach($_dirs as $_key => $_val) {
            if ($_val==$_appDir) {
                return $_val;
                break;
            }
        }
    }

    function checkAppFile ($_appDir, $_appFile) {
        $_dir=__DIR__."/../../apps/$_appDir/";
        $_files = array_slice(scandir($_dir), 2);
        foreach($_files as $_key => $_val) { 
            if ($_val==$_appFile) {
                return $_val;
                break;
            }
        }
    }
}