<?php

/********************************************************************
*	Date		: Thursday, August 25, 2011
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*********************************************************************/

session_start();

//--------load configuration

include(__DIR__.'/config.php');

//--------load classes

require_once __DIR__."/../vendor/autoload.php";

$api = new Gov2lib\api\api;
$dsn = new Gov2lib\env\dbConnect;
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
		$page = new $handler;
		/*
		if (is_array($_GET)) {
			while (list($key,$val)=each($_GET)) {
			    $val=strip_tags($val);
			    if (preg_match('/[^a-zA-Z0-9_.]/', $val)) {throw new Exception('IlegalQueryString');} 
			    else {$_GET[$key]=$val;}
			}
		}
		*/
    break;
}

//--------templating

$loader = new Twig_Loader_Filesystem(array(__DIR__.'/../template/base',$page->templateDir));
$escaper = new Twig_Extension_Escaper('html');
//$twig = new Twig_Environment($loader, array('cache'=> __DIR__.'/../template/cache')); //---- for prod env
$twig = new Twig_Environment($loader, array('auto_reload'=> true)); //---- for dev env
$twig->addExtension($escaper);
$template = $twig->load($page->baseName.'Body.html');
