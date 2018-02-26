<?php
/********************************************************************
*	Date		: Mon, 9 Okt, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*********************************************************************/

try {
    if (!isset($page)) {
        $handler = "App\home\model\index";
        $page = new $handler;
    }
    array_push($templates,$page->templateDir);
    $doc->body('pageID',$page->baseName);
    if (isset($vars[$page->baseName.'_id'])) { 
        $doc->body($page->baseName.'_id',$vars[$page->baseName.'_id']);
    }
    if ($httpMethod=='GET') {$doc->body('_GET',$vars);}
    else {$doc->body('_POST',$_POST);}
    $pageID=$page->baseName;
    $doc->body('STAGE',STAGE);
	$doc->component($pageID);
	$loader = new Twig_Loader_Filesystem($templates);
	$escaper = new Twig_Extension_Escaper('html');
	//$twig = new Twig_Environment($loader, array('cache'=> __DIR__.'/../template/cache')); //---- for prod env
	$twig = new Twig_Environment($loader, array('auto_reload'=> true)); //---- for dev env
	$twig->addExtension($escaper);
    if (!file_exists($page->templateDir."/".$page->baseBody)) {
        throw new Exception('NoBasedBodyFileFound:'.$page->baseBody);
    }
} catch (Exception $e) {
    $doc->exceptionHandler($e->getMessage());
}
if ($doc->error) {$page->baseBody="bulmaBody.html";}