<?php namespace Gov2lib;
    
class options {
    function __construct () {
        global $self,$vars,$cmdID, $pageID;
		$self->takeAll("components");
        if ($pageID !== 'rokuone') {
            $self->ses->takeAll('rokuone');
        }
//        $self->take("dpdraft2","draft");
//        $self->take($vars['app'],"index","dependencies");
        $self->ses->authenticate("webmaster"); //---perlu direview
    }
    
    function index () {
        global $self,$doc,$vars,$cmdID;
        $self->gov2nav->setDefaultNavCustom();
        $role = isset($vars['role']) ? $vars['role'] : 'Options';
        $doc->body("pageTitle",ucwords($vars['app'])." " .ucwords($role));
        if ($cmdID=='setup') {
            $self->content("option_setup.html");
        } elseif ($cmdID === 'view' || $cmdID === 'view_services') {
            $doc->body('view_type', $cmdID);
            $self->content("option_view.html");
        } elseif ($cmdID === 'controlpanel') {
            $self->content("option_controlpanel.html");
        }
        
    }
}
