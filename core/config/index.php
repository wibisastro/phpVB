<?php
/********************************************************************
*	Date		: Thursday, 05 Okt, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*********************************************************************/
//----todo pisahkan list domain ke file xml
try { 
    $publickey="c65ca73ce4c38dcec21151aa64f1590c";
    $stages=array('dev','cybergl','biznet','drc','bppt','bssn','local');
    foreach($stages AS $stage) {
        if (file_exists(__DIR__."/config.".$stage.".xml")) {
            $config = simplexml_load_file(__DIR__."/config.".$stage.".xml");
            if (is_object($config)) {
                if ($config->domain->{$_SERVER["SERVER_NAME"]}) {
                    define('STAGE',$stage);
                    break;
                }
            } else {
                throw new Exception('InvalidConfigFile');
            }
        } else {
            throw new Exception('ConfigFileNotExist:'.__DIR__.'/config.'.$stage.'.xml');
        }
    }
    switch (STAGE) {
        case "drc":
        case "cybergl":
        case "local":
            error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
            ini_set("display_errors", 1);
        break;
        case "dev":
            ini_set("display_errors", 1);
            $_GET['error']=isset($_GET['error']) ? $_GET['error'] : '';
            switch ($_GET['error']) {
                case "all":error_reporting(E_ALL);break;
                case "warning":error_reporting(E_ALL & ~E_NOTICE);break;
                default:error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);break;
            }
            $config->domain->attr['dsn']='master';
            $config->domain->attr['id']='25823';
        break;
        case "bssn":
        case "bppt":
        case "biznet":
//            error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
        break;
        default:
            throw new Exception('UnConfiguredDomain');
	}
    if ($config->secure==true) {
        $config->protocol="https";
    } else {
        $config->protocol="http";
    }
    if (STAGE != 'dev') {
        foreach($config->domain->{$_SERVER["SERVER_NAME"]}->attributes() as $k => $v) {
            $config->domain->attr[$k]=$v;
        }
        $config->domain->attr['dsn']=$_SERVER["SERVER_NAME"];
    }
} catch (Exception $e) { 
	echo $e->getMessage();
    exit;
} 
?>