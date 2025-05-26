<?php namespace App\gov2login;

class eselon2 {
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
        $response=$self->postTagging($_POST,"renstra","member",'','fullname');
        return $response;
    }
    
    function unSetTag () {
        global $self;
        $response=$self->postDelTag($_POST);
        return $response;
    }
    
    function getTags ($vars) {
        global $self,$config,$scriptID;
        if ($vars['id']==-1) {
            $_id=$self->setRememberId("-1","renstra");
        } else {
            $_id=$vars['id'];
        }
        $response=$self->getBrowseTags($_id,"renstra","member",'','fullname');
        return $response;
    }
}
