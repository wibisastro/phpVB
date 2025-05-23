<?php namespace App\gov2login;
    
class guest {
    function __construct () {  
		global $self;
        $self->takeAll("components");
        $self->scrollInterval=100;
        $self->ses->authenticate('member');
    }
    
    function index () {
        global $self,$doc;
        $doc->body("pageTitle",'Gov 2.0 Guest | Member | Admin Page'); 
        $self->loadTable($self->scrollInterval);
        $self->content('member.html');
        $self->gov2notification->content();
        $self->content('tableGuest.html');
        $self->gov2pagination->content();
    }

    function count ($vars) {
        global $self,$doc;
        $data=$self->guestCount();
        return $doc->responseGet($data);   
    }
    
    function fields () {
        global $self;
        return $self->fields;
    }

     function table () {
        global $doc,$self;
        $data=$self->guestBrowse($vars['scroll']);
        if (sizeof($data)==0) {$data=array("data"=>"empty","level"=>"1");}
        return $doc->responseGet($data);   
    }
}