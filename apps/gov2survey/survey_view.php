<?php namespace App\gov2survey;

class survey_view extends \Gov2lib\api {
    function __construct()
    {
        global $self;
        $self->ses->authenticate('member');
        parent::__construct();
        $self->takeAll("components");
        $self->takeAll("rokuone");
        $self->scrollInterval=100;
        // $self->ses->authenticate('maintenance','5.30 AM');
        // var_dump(\DB::queryFirstRow('SELECT DATABASE() AS DB'));exit;
        // \DB::debugMode();
    }

    function index ()
    {
        global $self,$doc;
        $self->gov2nav->setDefaultNavCustom();
        $doc->body("pageTitle",'Hasil Survey');
        $self->loadTable();
        $self->content();
    }

    function breadcrumb () {
        global $self, $vars;
        return $self->getBreadcrumb("Survey");
    }

    function table ($vars)
    {
        global $self, $doc;
        $data = $self->browse($vars);
        return $doc->responseGet($data);
    }

    function count ($vars)
    {
        global $self, $doc;
        $parent_id = $self->setRememberId($vars['id']);
        $data = $self->count($parent_id);
        return $doc->responseGet($data);
    }

    function kerjasama ()
    {
        global $self, $doc;
        $data = $self->get_kerjasama();
        return $doc->responseGet($data);   
    }
}