<?php
/********************************************************************
*	Date		: Thursday, 05 Okt, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*********************************************************************/

try { 
    $publickey="c65ca73ce4c38dcec21151aa64f1590c";
    $host=explode(".",$_SERVER["SERVER_NAME"]);
    switch ($_SERVER["SERVER_NAME"]) {
        case "dak.bappeda.web.id":
        case "api.kl2.web.id":
		case "api.local.krisna.systems":
        case $host[0].".local.vlsm.org":
            define('STAGE','build');
            error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
//			error_reporting(E_ALL);
            ini_set("display_errors", 1);
        break;
        case "localhost":
            define('STAGE','dev');
            ini_set("display_errors", 1);
            $_GET['error']=isset($_GET['error']) ? $_GET['error'] : '';
            switch ($_GET['error']) {
                case "all":error_reporting(E_ALL);break;
                case "warning":error_reporting(E_ALL & ~E_NOTICE);break;
                default:error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);break;
            }
        break;
        case "api.krisna.systems":
        case $host[0].".vlsm.org":
            define('STAGE','prod');
            error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
            ini_set("display_errors", 1);
        break;
        default:
            throw new Exception('UnRegisteredDomain');
	}
    $config = simplexml_load_file(__DIR__."/config.".STAGE.".xml");
    if (file_exists(__DIR__."/config.".STAGE.".xml")) {
        if (is_object($config)) {
            if ($config->secure==true) {
                $config->protocol="https";
            } else {
                $config->protocol="http";
            }
        } else {
            throw new Exception('InvalidConfigFile');
        }
    } else {
        throw new Exception('ConfigFileNotExist:'.__DIR__.'/config.'.STAGE.'.xml');
    }
} catch (Exception $e) { 
	echo $e->getMessage();
    exit;
} 
?>