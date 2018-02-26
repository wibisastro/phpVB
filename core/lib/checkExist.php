<?php namespace Gov2lib;
/********************************************************************
*	Date		: Monday, Okt 28, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*	Version		: 1.0.0 
*********************************************************************/

class checkExist {
    function checkAppDir ($_appDir) {
        $_dir=__DIR__."/../../apps";
        $_dirs = array_slice(scandir($_dir), 2);
        while (list($_key,$_val)=each($_dirs)) {
            if ($_val==$_appDir) {
                return $_val;
                break;
            }
        }
    }

    function checkAppFile ($_appDir, $_appFile) {
        $_dir=__DIR__."/../../apps/$_appDir/";
        $_files = array_slice(scandir($_dir), 2);
        while (list($_key,$_val)=each($_files)) { 
            if ($_val==$_appFile) {
                return $_val;
                break;
            }
        }
    }
}