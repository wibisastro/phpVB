<?php

namespace Gov2lib;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;

/**
 * Login controller
 *
 * @version 4.2
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
     * Display login page — redirect to profile if already logged in
     */
    public function index(): void
    {
        global $self, $doc, $config, $pageID;

        if ($self->ses->val['account_id'] ?? false) {
            header("Location: /$pageID/profile");
            exit;
        }

        $self->ses->authenticate('public');
        $doc->body("pageTitle", 'Silakan Login');
        // Form login beo (di-iframe) sudah punya logo + judul sendiri → sembunyikan
        // heading portal "Silakan Login" biar form lebih dekat ke topbar.
        $doc->body('hideTitle', 1);
        $doc->body('brandName', (trim((string)($config->title ?? '')) ?: ($_SERVER['SERVER_NAME'] ?? '')));
        $_SESSION['ssonode'] = trim((string) ($config->platform->ssonode ?? ''));
        $self->content("@gov2login/notLogin.html");
    }

    /**
     * Display signup page — redirect to profile if already logged in
     */
    public function signup(): void
    {
        global $self, $doc, $pageID;

        if ($self->ses->val['account_id'] ?? false) {
            header("Location: /$pageID/profile");
            exit;
        }

        $self->ses->authenticate('public');
        $doc->body("pageTitle", 'Gov 2.0 SSO Signup');
        $self->content("@gov2login/signup.html");
    }

    /**
     * Display user profile page — redirect to login if not logged in
     */
    public function profile(): void
    {
        global $self, $doc, $pageID;

        if (!($self->ses->val['account_id'] ?? false)) {
            header("Location: /$pageID/login");
            exit;
        }

        $self->ses->authenticate('guest');
        $doc->body("pageTitle", 'Profil');
        $self->content("@gov2login/profile.html");
    }

    /**
     * Display forgot password page
     */
    public function forgot(): void
    {
        global $self, $doc;
        $doc->body("pageTitle", 'Gov 2.0 SSO Forgot Password');
        $self->content("@gov2login/forgot.html");
    }

    /**
     * Display reset password page — redirect to profile if already logged in
     */
    public function resetpass(): void
    {
        global $self, $doc, $pageID;

        if ($self->ses->val['account_id'] ?? false) {
            header("Location: /$pageID/profile");
            exit;
        }

        $self->ses->authenticate('public');
        $doc->body("pageTitle", 'Lupa Password');
        $self->content("@gov2login/resetpass.html");
    }

    /**
     * Display activation page — redirect to profile if already logged in
     */
    public function activate(): void
    {
        global $self, $doc, $pageID;

        if ($self->ses->val['account_id'] ?? false) {
            header("Location: /$pageID/profile");
            exit;
        }

        $self->ses->authenticate('public');
        $doc->body("pageTitle", 'Aktivasi Akun');
        $self->content("@gov2login/activate.html");
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
