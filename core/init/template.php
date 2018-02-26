<?php
/********************************************************************
*	Date		: Mon, 9 Okt, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*********************************************************************/
//$doc->body('webroot',$config->webroot);
try {
    if ($httpMethod=='GET') {
        $doc->body('_GET',$vars);
    } else {
        $doc->body('_POST',$_POST);
    }

    $doc->body('STAGE',STAGE);
    $doc->body('pageID',$pageID);
    $doc->component($pageID);

    if (file_exists($self->templateDir."/body.html")) {
        $doc->baseBody="@$pageID/body.html";
    } else {
        $doc->baseBody="bulmaBody.html";
    }

    if ($doc->error) {
        $doc->baseBody="bulmaBody.html";
    }

    $templates=array(__DIR__.'/../template/bulma');

    $loader = new Twig_Loader_Filesystem($templates);
    $twig = new Twig_Environment($loader, 
        array(
            'auto_reload'=> true,
//            'strict_variables'=>true
        )
    );

    $loader->addPath($self->templateDir,$pageID);

    $escaper = new Twig_Extension_Escaper('html');
    $twig->addExtension($escaper);
} catch (Exception $e) {
    $doc->exceptionHandler($e->getMessage());
}