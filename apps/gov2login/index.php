<?php namespace App\gov2login;

/********************************************************************
*	Date		: 19 April 2018
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@cybergl.co.id
*	Copyright	: Cyber Gov Labs. All rights reserved.
*********************************************************************/

class index {
    function __construct () {
        global $self, $config, $doc;
        $self->take("components","gov2notification");
        $self->externalJS('js.html');
        // Nama portal (config <title>) utk brand navbar — ganti default "phpVB".
        $doc->body('brandName', (trim((string)($config->title ?? '')) ?: ($_SERVER['SERVER_NAME'] ?? '')));
    }
    
    function index () {
        global $self,$doc;
        $doc->body("pageTitle",'Gov 2.0 SSO Login');
        if ($self->ses->val['account_id'] ?? false) {
            // Halaman profil portal pensiun — profil beo ber-tab (adopsi sesi stoken).
            header("Location: " . profile::ssoProfileUrl());
            exit;
        } else {
            $self->ses->authenticate('public');
            $doc->body('hideTitle', 1);   // form login beo (iframe) sudah berjudul
            $self->content("notLogin.html");
        }
    }
    
    function signup () {
        global $self,$doc;
        $doc->body("pageTitle",'Gov 2.0 SSO Signup');
        $doc->body('hideTitle', 1);   // form beo (di-iframe) sudah punya judul sendiri
        $self->content("signup.html");
    }
    
    function profile () {
        // Halaman profil portal pensiun — profil beo ber-tab (adopsi sesi stoken).
        header("Location: " . profile::ssoProfileUrl());
        exit;
    }
    
    function forgot () {
        global $self,$doc;
        $doc->body("pageTitle",'Gov 2.0 SSO Forgot Password');
        $doc->body('hideTitle', 1);
        $self->content("forgot.html");
    }
    
    function webservice () {
        global $self,$doc;
        echo file_get_contents("https://sso.gov2.web.id/index.php?cmd=check");
        exit;
    }
}
?>