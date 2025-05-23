<?php namespace Gov2lib;
    
class role {
    function __construct () {
        global $self,$vars, $pageID;
        // $self->ses->authenticate('admin');
		$self->takeAll("components");
        $self->take($vars['app'],"index","dependencies");
        $self->ses->takeAll($vars['app']);
    }
    
    function index () {
        global $self,$doc,$cmdID,$vars;

        $self->ses->authenticate($vars['role']);
        $self->gov2nav->setDefaultNavCustom();
        $doc->body("pageTitle",'Gov 2.0 SSO '.ucfirst($cmdID).' Role');
        $self->content("role.html");
    }
}
