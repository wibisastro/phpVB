<?php namespace App\gov2login;
// session_start(); // -- Commented by Rijal 21 Nov 2023

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use League\OAuth2\Client\Provider\GenericProvider;

/********************************************************************
*	Date		: 19 April 2018
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@cybergl.co.id
*	Copyright	: Cyber Gov Labs. All rights reserved.
*********************************************************************/

class login {
    function __construct () {
        global $self, $doc;
        $doc->baseBody="index.html";
    }
    
    function index () {
        global $self,$doc;
        $doc->body("pageTitle",'Gov 2.0 SSO Login Handler');
        $self->content();
    }
    
    function sessave ($vars) {
        global $self,$doc;
        $doc->body("token",$vars['token']);
        $doc->body("ssonode",$vars['ssonode']);
        $self->content('redirect.html');
    }
    
    function siasn ($vars) {
        global $self,$doc;
        $doc->body("token",$vars['token']);
        $doc->body("ssonode","SIASN");
        $self->content('redirect.html');
    }
    
    function session ($vars) {
        global $self;
        $self->createSession($vars);
    }

    function cloakcode($vars)
    {
        global $self, $config;
        $keycloak = $config->keycloak;
        if ($keycloak && (int)$keycloak->active) {
            if (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {

                if (isset($_SESSION['oauth2state'])) {
                    unset($_SESSION['oauth2state']);
                }

                exit('Invalid state');
            }

            try {
                $options = array(
                    'clientId' => (string)$config->domain->attr['clientId'],
                    'clientSecret' => (string)$config->domain->attr['clientSecret'],
                    'urlAuthorize' => str_replace("{realm}", (string)$config->domain->attr['realm'], (string)$keycloak->urlAuthorize),
                    'urlAccessToken' => str_replace("{realm}", (string)$config->domain->attr['realm'], (string)$keycloak->urlAccessToken),
                    'urlResourceOwnerDetails' => str_replace("{realm}", (string)$config->domain->attr['realm'], (string)$keycloak->urlResourceOwnerDetails)
                );
                $provider = new GenericProvider($options);

                $self->createKeycloakSession($provider);

            } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

                // Failed to get the access token or user details.
                exit($e->getMessage());
            }
        } else {
            exit("KeyCloak inactive");
        }
    }
    
    function pipe ($vars) {
        global $self;
        $endpoint = $self->opt->get(['nama' => 'krisna_authorize']);
        $krisna_authorize_endpoint = $endpoint['value'];
        $vars['service'] = $krisna_authorize_endpoint;
        $self->createPipe($vars);
    }
    
    function login () {
        global $config;
        header("Location: ".$config->platform->ssonode."/slogin.php?cmd=request&client=".$_SERVER["SERVER_NAME"]);
        exit;
    }
    
    function logout () {
        global $config,$self;
        $self->ses->sesReset();
        $keycloak = $config->keycloak;
        $logoutpath = (string)$config->platform->logoutpath;
        if(!$logoutpath) {
            $logoutpath = '/slogout.php';
        }

        if ($keycloak && (int)$keycloak->active) {
            $client = new Client;
            $endpoint  = str_replace("{realm}",
                (string)$config->domain->attr['realm'],
                (string)$keycloak->urlPurgeToken);
            try {
                $client->post($endpoint, [
                    'form_params' => [
                        'client_id' => (string)$config->domain->attr['clientId'],
                        'client_secret' => (string)$config->domain->attr['clientSecret'],
                        'refresh_token' => $_SESSION['refresh_token']
                    ]
                ]);
            } catch (ClientException $e) {
                $response_body = $e->getResponse()->getBody()->getContents();
                $resp = json_decode($response_body, 1);
                $self->exceptionHandler($resp['error_description'].":".$resp['error_description']);
            } catch (\Exception $e) {
                $self->exceptionHandler($e->getMessage());
            }
        }

        $_target_url = $config->platform->ssonode.$logoutpath."?client={$_SERVER["SERVER_NAME"]}";
        header("Location: ".$_target_url);
        exit;

    }
}
?>