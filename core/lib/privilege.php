<?php

namespace Gov2lib;

/**
 * Privilege controller
 */
class privilege
{
    /**
     * Initialize privilege controller
     */
    public function __construct()
    {
        global $self, $vars;
        $self->takeAll("components");
        $self->take($vars['app'], "index", "dependencies");
    }

    /**
     * Display privilege management page
     */
    public function index(): void
    {
        global $self, $doc, $cmdID, $vars;
        $self->ses->authenticate($vars['privilege']);
        $doc->body("pageTitle", 'Gov 2.0 SSO ' . ucfirst($cmdID) . ' Privilege');
        $self->content("privilege.html");
    }
}
