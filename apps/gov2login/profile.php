<?php namespace App\gov2login;
    
class profile {
    function __construct () {  
		global $self;
        $self->takeAll("components");
        $self->ses->authenticate('guest');
    }
    
    function index () {
        global $self,$doc;
        $doc->body("pageTitle",'Gov 2.0 Profile');
        $self->content('profile.html');
    }
}