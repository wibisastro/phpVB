<?php namespace App\gov2login;

/********************************************************************
*	Date		: 19 April 2018
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@cybergl.co.id
*	Copyright	: Cyber Gov Labs. All rights reserved.
*********************************************************************/

class index {
    function __construct () {
        global $self;
        $self->take("components","gov2notification");
        $self->externalJS('js.html');
    }
    
    function index () {
        global $self,$doc;
        $doc->body("pageTitle",'Gov 2.0 SSO Login');
        $self->ses->authenticate('public');
        if ($self->ses->val['account_id']) {$self->content("profile.html");}
        else {$self->content("notLogin.html");}
    }
    
    function signup () {
        global $self,$doc;
        $doc->body("pageTitle",'Gov 2.0 SSO Signup');
        $self->content("signup.html");
    }
    
    function profile () {
        global $self,$doc;
        $doc->body("pageTitle",'Gov 2.0 SSO Profile');
        $self->ses->authenticate('guest');
        $self->content("profile.html");
    }
    
    function forgot () {
        global $self,$doc;
        $doc->body("pageTitle",'Gov 2.0 SSO Forgot Password');
        $self->content("forgot.html");
    }
    
    function webservice () {
        global $self,$doc;
        echo file_get_contents("https://sso.gov2.web.id/index.php?cmd=check");
        exit;
    }
}
?>