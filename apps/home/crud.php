<?php namespace App\home;

class crud extends \Gov2lib\api {

    function __construct() {
       global $self;
	   $self->takeAll("components");
       parent::__construct();
       $self->scrollInterval=300;
       $self->fields = $self->gov2formfield->getFields(__DIR__ . "/json/crud.json");
    }

    function index() {
        global $self,$doc;
        $self->take("components","gov2nav", "setDefaultNav");
        $doc->body("pageTitle",'Contoh CRUD');
        $doc->body("subTitle",'Daftar Aset');
        $self->loadTable();
        $self->gov2notification->content();
        $self->gov2search->content();
        $self->gov2button->content();
        $self->content();
        $self->gov2pagination->content();
    }

    function fields () {
        global $self;
        return $self->fields;
    }

    function edit ($vars) {
        global $self;
        $response=$self->getRecord($vars['id']);
        return $response;
    }

    function count ($vars) {
        global $self;
        $response=$self->getCount($vars['id']);
        return $response;
    }

    function table ($vars) {
        global $self, $doc, $scriptID;
        $data = $self->getRecords($vars['scroll']);
        if (sizeof($data) == 0) {
            $data = array("data" => "empty", "level" => "1");
        }
        return $doc->responseGet($data);
    }

    function add () {
        global $self;

        unset($_POST['id']);

        $response=$self->postAdd($_POST);
        return $response;
    }

    function update () {
        global $self;

        $response=$self->postUpdate($_POST);
        return $response;
    }

    function del () {
        global $self;
        $response=$self->postDel($_POST);
        return $response;
    }

}