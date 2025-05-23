<?php namespace App\gov2login;

class eselon1 {
    function __construct () {
        global $self;
        $self->takeAll("components");
        $self->ses->authenticate('admin');
    }
    
    function index () {
        global $self,$doc;
        $doc->body("pageTitle",'User Privilege');
        $self->gov2notification->content();
        $self->loadTable($self->scrollInterval);
        $self->content();
    }
        
    function setTag () {
        global $self;
        $response=$self->postTagging($_POST,"kementerian","member",'','fullname');
        return $response;
    }
    
    function unSetTag () {
        global $self;
        $response=$self->postDelTag($_POST);
        return $response;
    }
    
    function getTags ($vars) {
        global $self,$config,$scriptID;
        if (!$vars['id'] || $vars['id']==0) {
            $_id=trim($config->domain->attr['id']);
        } else {
            $_id=$vars['id'];
        }
        $response=$self->getBrowseTags($_id,"kementerian","member",'','fullname');
        return $response;
    }
    /*
    function table ($vars) {
        global $doc,$self;
        $data=$self->memberBrowse($vars['scroll']);
        if (sizeof($data)==0) {$data=array("data"=>"empty","level"=>"1");}
        return $doc->responseGet($data);
    }
    */
}
