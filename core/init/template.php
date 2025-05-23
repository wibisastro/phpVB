<?php
/********************************************************************
*	Date		: Mon, 9 Okt, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI
*********************************************************************/
//$doc->body('webroot',$config->webroot);
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

try {
    if ($httpMethod=='GET') {
        $doc->body('_GET',$vars);
    } else {
        $doc->body('_POST',$_POST);
    }

    $doc->body('STAGE',STAGE);
    $doc->body('pageID',$pageID);
    $doc->component($pageID);
    $doc->body('SSONODE',$config->platform->ssonode);

    if (file_exists($self->templateDir."/body.html")) {
        $doc->baseBody="@$pageID/body.html";
    } else {
        $doc->baseBody="bulmaBody.html";
    }

    if ($doc->error) {
        if ($doc->error['Forbidden']) {
            $doc->baseBody="error402.html";
        } else if ($doc->error['RouterConfigFileNotExist']) {
            $doc->baseBody="error404.html";
        } else if (
                    $doc->error['UnConfiguredDomain'] ||
                    $doc->error['InvalidConfigFile'] ||
                    $doc->error['ConfigFileNotExist']
                    ) {
            $doc->baseBody="error500.html";
        } else {
            $doc->baseBody="errorBody.html";
        }

    }

    $templates=array(__DIR__.'/../template/bulma',
                     __DIR__.'/../template/bootstrap',
                     __DIR__.'/../template/cube',
                     __DIR__.'/../template/krisna',
                     __DIR__.'/../template/general');

    $vueData['isNavToggle'] = false;

    $loader = new FilesystemLoader($templates);

    $twig = new Environment($loader,
        array(
            'auto_reload'=> true,
            'debug' => true,
            // 'strict_variables'=>true
        )
    );

    $loader->addPath((string) $self->templateDir,$pageID);
    /**No required for twig version 2.16 or later */
    // $escaper = new Twig_Extension_Escaper('html');
    // $twig->addExtension($escaper);
    $twig->addExtension(new \Twig\Extension\DebugExtension());

} catch (Exception $e) {
    $doc->exceptionHandler($e->getMessage());
}