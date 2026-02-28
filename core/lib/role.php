<?php

namespace Gov2lib;

/**
 * Role controller
 */
class role
{
    /**
     * Initialize role controller
     */
    public function __construct(): void
    {
        global $self, $vars, $pageID;
        $self->takeAll("components");
        $self->take($vars['app'], "index", "dependencies");
        $self->ses->takeAll($vars['app']);
    }

    /**
     * Display role management page
     */
    public function index(): void
    {
        global $self, $doc, $cmdID, $vars;

        $self->ses->authenticate($vars['role']);
        $self->gov2nav->setDefaultNavCustom();
        $doc->body("pageTitle", 'Gov 2.0 SSO ' . ucfirst($cmdID) . ' Role');
        $self->content("role.html");
    }
}
