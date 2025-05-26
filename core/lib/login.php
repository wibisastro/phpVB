<?php namespace Gov2lib;
// session_start(); // -- Commented by Rijal 21 Nov 2023

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;

/**
 * Class login
 * @package Gov2lib
 *
 * # ---- ver 4.1, 05 April 2021, [rijal@cybergl.co.id], Menambahkan support keycloak BKN @line 22-31. Ref task : https://projects.cybergl.co.id/issues/3813
 */

class login {
    function __construct () {
        global $self,$vars;
		$self->takeAll("components");
        $self->take($vars['app'],"index","dependencies");
    }
    
    function index () {
        global $self,$doc, $config;
        $is_gov2 = false;
        if(isset($_GET['type']) && $_GET['type'] === 'gov2') {
            $is_gov2 = true;
        }
        $self->ses->authenticate('guest');
        $keycloak = $config->keycloak;
        if ($keycloak && (int)$keycloak->active && !$is_gov2) {
            $doc->body('_SESSION', $self->ses->val);
            $doc->body("pageTitle",'KeyCloak Profile');
            $self->content("profile_keycloak.html");
        } else {
            $doc->body("pageTitle",'Gov 2.0 SSO Profile');
            $self->content("profile.html");
        }
    }
    
    function signup () {
        global $self,$doc;
        $doc->body("pageTitle",'Gov 2.0 SSO Signup');
        $self->content("signup.html");
    }
    
    function profile () {
        global $self,$doc;
        $self->ses->authenticate('guest');
        $doc->body("pageTitle",'Gov 2.0 SSO Profile');
        $self->ses->authenticate('guest');
        $self->content("profile.html");
    }
    
    function forgot () {
        global $self,$doc;
        $doc->body("pageTitle",'Gov 2.0 SSO Forgot Password');
        $self->content("forgot.html");
    }
}