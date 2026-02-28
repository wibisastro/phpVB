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
    public function __construct(): void
    {
        global $self, $vars;
        $self->takeAll("components");
        $self->take($vars['app'], "index", "dependencies");
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
}
