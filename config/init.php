<?php

/********************************************************************
*	Date		: Thursday, August 25, 2011
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*********************************************************************/

//--------error_reporting (E_ALL & ~E_NOTICE & ~E_WARNING); 
#--instalation helper, must be shut off upon success
error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);

//--------constant

define("CSS_URL","/css");
define("JS_URL","/js");

//--------autoloading

require_once __DIR__."/../vendor/autoload.php";

$api = new Gov2lib\api\api;
$doc = new Gov2lib\env\document;
$ses = new Gov2lib\env\session;
$exc = new Gov2lib\env\customException;
$frm = new Gov2lib\helper\formage;

//--------routing

include("route.php");

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        throw new Exception(NOT_FOUND);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        throw new Exception('Method '.METHOD_NOT_ALLOWED.' is not Alowed');
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1]."\model\index";
        $vars = $routeInfo[2];
		$found = new $handler;
        break;
}

//--------templating

$loader = new Twig_Loader_Filesystem(array(__DIR__.'/../template/base',$found->templateDir));
$escaper = new Twig_Extension_Escaper('html');
//$twig = new Twig_Environment($loader, array('cache'=> __DIR__.'/../template/cache')); //---- for prod env
$twig = new Twig_Environment($loader, array('auto_reload'=> true)); //---- for dev env
$twig->addExtension($escaper);
$template = $twig->load($found->baseName.'Body.html');
