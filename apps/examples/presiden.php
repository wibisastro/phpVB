<?php namespace App\examples;

class presiden {
    function __construct () {
        global $self;
        $self->takeAll("components");
        $self->scrollInterval=20;
        $self->fields = $self->gov2formfield->getFields(__DIR__."/json/presiden.json");
    }
    
    function index () {
        global $self,$doc;
        $self->gov2nav->setDefaultNav();
        $doc->body("pageTitle",'Janji Presiden');
        $self->loadTable($self->scrollInterval);
        $self->gov2formfield->content();
        $self->gov2notification->content();
        $self->content();
        $self->gov2pagination->content();
    }
    
    function count ($vars) {
        global $self;
        $response=$self->getCount($vars['id']);
        return $response;
    }
    
    function fields () {
        global $self;
        return $self->fields;
    }
    
    function table ($vars) {
        global $self;
        $response=$self->getRecords($vars);
        return $response;
    }
    
    function children ($vars) {
        global $self;
        $response=$self->getChildren($vars['id']);
        return $response;
    }
    
    function edit ($vars) {
        global $self;
        $response=$self->getRecord($vars['id']);
        return $response;
    }
    
    function add () {
        global $self;
        $response=$self->postAdd($_POST);
        return $response;
    }
    
    function del () {
        global $self;
        $response=$self->postDel($_POST);
        return $response;
    }
    
    function update () {
        global $self;
        $response=$self->postUpdate($_POST);
        return $response;
    }
}