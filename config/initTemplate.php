<?php

/********************************************************************
*	Date		: Mon, 9 Okt, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*********************************************************************/

try {
	$doc->body('pageID',$page->baseName);
	$doc->body($page->baseName.'_id',$vars[$page->baseName.'_id']);
	if ($httpMethod=='GET') {$doc->body('_GET',$vars);}
	else {$doc->body('_POST',$_POST);}
	//---default pageID, replace with other to make custom pageID
	$pageID=$page->baseName;

	$loader = new Twig_Loader_Filesystem($templates);
	$escaper = new Twig_Extension_Escaper('html');
	//$twig = new Twig_Environment($loader, array('cache'=> __DIR__.'/../template/cache')); //---- for prod env
	$twig = new Twig_Environment($loader, array('auto_reload'=> true)); //---- for dev env
	$twig->addExtension($escaper);
} catch (Exception $e) {
	echo $e->getMessage();
}	