<?php namespace Gov2lib;
    
class privilege {
    function __construct () {
        global $self,$vars;
		$self->takeAll("components");
        $self->take($vars['app'],"index","dependencies");
    }
    
    function index () {
        global $self,$doc,$cmdID,$vars;  
        $self->ses->authenticate($vars['privilege']); //---perlu direview
        $doc->body("pageTitle",'Gov 2.0 SSO '.ucfirst($cmdID).' Privilege');
        $self->content("privilege.html");
    }
}
