<?php namespace App\gov2pipe;

class pipedin extends \Gov2lib\api {
    function __construct () {
        global $self,$doc;
        parent::__construct();
        $_className=$self->className;
		$self->takeAll("components");
        $self->scrollInterval=100;
        $doc->body("className",$_className);
        $self->ses->authenticate('public');
        $this->DEBUG_MODE = strpos($_SERVER['SERVER_NAME'], 'bkn.go.id') !== false;
    }

    function index () {
        global $self,$doc;
        $self->gov2nav->setDefaultNavCustom();
        $endpoint_option = $self->opt->get(['nama' => 'krisna_home']);
        $krisna_home_endpoint = $endpoint_option['value'];
        $_authorized=$self->openPipe($krisna_home_endpoint);
        $doc->body("pageTitle",'Piped-In');
        $doc->body('pipedin', $_authorized);
        $self->content();
    }
    
    function logout () {
        global $self,$doc;
        $self->gov2nav->setDefaultNavCustom();
        $endpoint_option = $self->opt->get(['nama' => 'krisna_logout']);
        $krisna_logout_endpoint = $endpoint_option['value'];
        $_authorized=$self->openPipe($krisna_logout_endpoint);
        header("location: /gov2pipe/pipedin");
    }
}