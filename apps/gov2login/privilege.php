<?php namespace App\gov2login;

class privilege {
    function __construct () {
        global $self, $doc;
        $self->takeAll("components");
        $self->ses->authenticate('admin');
        $doc->baseBody = '@gov2login/b4body.html';
    }
    
    function index () {
        global $self,$doc,$config;
        $doc->body("pageTitle",'User Privilege');
        $self->gov2notification->content();
        $self->loadTable($self->scrollInterval);
        $self->content();
    }
        
    function setTag () {
        global $self;
        $response=$self->postTagging($_POST,"wilayah","member",'','fullname');
        return $response;
    }
    
    function unSetTag () {
        global $self;
        $response=$self->postDelTag($_POST);
        return $response;
    }
    
    function getTags ($vars) {
        global $self;
        $response=$self->getBrowseTags($vars['id'],"wilayah","member",'','fullname');
        return $response;
    }
    
    function table ($vars) {
        global $doc,$self;
        $data=$self->memberBrowse($vars['scroll']);
        if (sizeof($data)==0) {$data=array("data"=>"empty","level"=>"1");}
        return $doc->responseGet($data);   
    }
}
