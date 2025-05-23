<?php namespace App\gov2login;
    
class admin {
    function __construct () {  
		global $self;
        $self->takeAll("components");
        $self->scrollInterval=100;
        $self->fields = $self->gov2formfield->getFields(__DIR__."/json/admin.json");
        $self->ses->authenticate('webmaster');
    }
    
    function index () {
        global $self,$doc;
        $doc->body("pageTitle",'Gov 2.0 Admin Admin'); 
        $self->loadTable($self->scrollInterval);
        $self->content('member.html');
        $self->gov2formfield->content();
        $self->gov2notification->content();
        $self->content('table.html');
        $self->gov2pagination->content();
        $self->content('propagasiButton.html');
    }

    function count ($vars) {
        global $self,$doc;
        $data=$self->memberCount();
        return $doc->responseGet($data);   
    }
    
    function fields () {
        global $self;
        return $self->fields;
    }

     function table () {
        global $doc,$self;
        $data=$self->memberBrowse($vars['scroll']);
        if (sizeof($data)==0) {$data=array("data"=>"empty","level"=>"1");}
        return $doc->responseGet($data);   
    }
/*
    function add () {
        global $self;
        $response=$self->postAdd($_POST);
        return $response;
    }
    */
    function edit ($vars) {
        global $self;
        $response=$self->getRecord($vars['id']);
        return $response;
    }
    /*
    function del () {
        global $self;
        $response=$self->postDel($_POST);
        return $response;
    }
    */
    function update () {
        global $self;
//        if () {}
        $response=$self->postUpdate($_POST);
        return $response;
    }
    
    function propagasiChecked ($vars) {
        global $self,$doc;
        $_affected=$self->propagasi($vars['propagasi'],$vars['role']);
        if (!is_array($doc->error)) {
            $response["notification"]="Data sebanyak $_affected baris berhasil dipropagasi";
            $response["callback"]="infoSnackbar";
            $response["class"]="is-info";
        } else {
            $response=$doc->response("is-danger","");
            header("HTTP/1.1 422 Query Fails");
        }
        return $response;
    }
}