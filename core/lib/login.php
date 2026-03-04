<?php

namespace Gov2lib;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;

/**
 * Login controller
 *
 * @version 4.1
 */
class login
{
    /**
     * Initialize login controller
     */
    public function __construct()
    {
        global $self, $vars, $doc, $loader;
        $self->takeAll("components");
        $self->take($vars['app'], "index", "dependencies");

        $loader->addPath(__DIR__ . '/../../apps/gov2login/view', 'gov2login');
        $doc->baseBody = '@gov2login/body.html';
    }

    /**
     * Display login page
     */
    public function index(): void
    {
        global $self, $doc, $config;
        $is_gov2 = (($_GET['type'] ?? null) === 'gov2');

        $self->ses->authenticate('guest');
        $keycloak = $config->keycloak;

        if ($keycloak && (int)$keycloak->active && !$is_gov2) {
            $doc->body('_SESSION', $self->ses->val);
            $doc->body("pageTitle", 'KeyCloak Profile');
            $self->content("profile_keycloak.html");
        } else {
            $doc->body("pageTitle", 'Gov 2.0 SSO Profile');
            $self->content("profile.html");
        }
    }

    /**
     * Display signup page
     */
    public function signup(): void
    {
        global $self, $doc;
        $doc->body("pageTitle", 'Gov 2.0 SSO Signup');
        $self->content("signup.html");
    }

    /**
     * Display user profile page
     */
    public function profile(): void
    {
        global $self, $doc;
        $self->ses->authenticate('guest');
        $doc->body("pageTitle", 'Gov 2.0 SSO Profile');
        $self->content("profile.html");
    }

    /**
     * Display forgot password page
     */
    public function forgot(): void
    {
        global $self, $doc;
        $doc->body("pageTitle", 'Gov 2.0 SSO Forgot Password');
        $self->content("forgot.html");
    }

    /**
     * Logout and redirect to SSO logout
     */
    public function logout(): void
    {
        global $config, $self;
        $self->ses->sesReset();

        $logoutpath = (string) ($config->platform->logoutpath ?? '');
        if (!$logoutpath) {
            $logoutpath = '/slogout.php';
        }

        $keycloak = $config->keycloak ?? null;
        if ($keycloak && (int) ($keycloak->active ?? 0)) {
            $client = new \GuzzleHttp\Client();
            $endpoint = str_replace(
                '{realm}',
                (string) ($config->domain->attr['realm'] ?? ''),
                (string) ($keycloak->urlPurgeToken ?? '')
            );
            try {
                $client->post($endpoint, [
                    'form_params' => [
                        'client_id' => (string) ($config->domain->attr['clientId'] ?? ''),
                        'client_secret' => (string) ($config->domain->attr['clientSecret'] ?? ''),
                        'refresh_token' => $_SESSION['refresh_token'] ?? '',
                    ],
                ]);
            } catch (\Exception $e) {
                // Log but don't block logout
            }
        }

        $_target_url = $config->platform->ssonode . $logoutpath . "?client={$_SERVER['SERVER_NAME']}";
        header("Location: " . $_target_url);
        exit;
    }
}
