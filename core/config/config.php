<?php
/********************************************************************
*	Date		: Thursday, 05 Okt, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*********************************************************************/

try { 
    $config = simplexml_load_file(__DIR__."/config.xml");
	$host=explode(".",$_SERVER["SERVER_NAME"]);
	switch ($_SERVER["SERVER_NAME"]) {
        case $host[0].".local.vlsm.org":
	        define('STAGE','build');
			error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
//			error_reporting(E_ALL);
            ini_set("display_errors", 1);
			$_GET['debug']=isset($_GET['debug']) ? $_GET['debug'] : '';
		break;
//	    case $host[0].".local.vlsm.org":
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
	    case $host[0].".vlsm.org":
			define('STAGE','prod');
			ini_set("display_errors", 0);
		break;
	    default:
        	throw new Exception('UnRegisteredDomain');
	}
//    echo STAGE;
} catch (Exception $e) { 
	echo $e->getMessage(); 
} 
?>