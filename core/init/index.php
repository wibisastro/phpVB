<?php

/********************************************************************
*	Date		: Sep, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*********************************************************************/

session_start();

//--------load configuration
require_once __DIR__.'/../config/index.php';

//--------autoloader

require_once __DIR__.'/../../vendor/autoload.php';

//--------load doc helper

$doc = new Gov2lib\document;

//--------routing

require_once 'route.php';

//--------templating

require_once 'template.php';

try {
	if ($_POST) { 
	    $cmd = $_POST['cmd'];
		$payload=$_POST;
	} else {    
	    $cmd = !$vars["cmd"] ? "index" : $vars["cmd"];
		$payload=$vars;
	}
    if (class_exists($controller)) {
        ${$pageID} = new $controller();
        if (!is_array($doc->error)) {
            if (method_exists(${$pageID},$cmd)) {
                $response=${$pageID}->$cmd($payload);
            } elseif ($cmd!="index") {
                throw new Exception('MethodNotExist: '.$cmd.'()');
            }            
        } else {
            $response=$doc->responseAuth();
        }
	} else {
		throw new Exception('ControllerClassNotExist: '.$controller);
	}
} catch (Exception $e) {
    if (!isset($self) || strstr($e->getMessage(),"ControllerClassNotExist")) {
        $handler = "App\home\model\index";
        $self = new $handler;
        $controller = "App\home\index";
        ${$pageID} = new $controller();
        $self->content();
    }
    $doc->exceptionHandler($e->getMessage());
}