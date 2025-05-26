<?php namespace Gov2lib;
    
class survey {
    function __construct () {
        global $self,$vars,$cmdID;
		$self->takeAll("components");
        $self->takeAll("rokuone");
//        $self->take($vars['app'],"index","dependencies");
        $self->ses->authenticate("webmaster"); //---perlu direview
    }
    
    function index () {
        global $self,$doc,$vars,$cmdID;
        $self->gov2nav->setDefaultNavCustom();
        $role = isset($vars['action']) ? $vars['action'] : 'Setup';
        $doc->body("pageTitle", strtoupper($vars['app'])." " .ucwords($role));
        if ($cmdID=='setup') {
            $doc->body("pageTitle", strtoupper($vars['app'])." " .'Kuesioner');
            $self->content("survey_setup.html");
        } elseif ($cmdID === 'view') {
            $doc->body("pageTitle", strtoupper($vars['app'])." " .'Survey');
            $self->content("survey_view.html");
        } elseif ($cmdID === 'result') {
            $doc->body("pageTitle", strtoupper($vars['app'])." " .'Hasil Survey');
            $self->content("survey_result.html");
        } else {
            $doc->body("pageTitle", 'Page Not Found');
            $self->content('error404.html');
        }
        
    }
}
