<?php

namespace Gov2lib;

/**
 * Options controller
 */
class options
{
    /**
     * Initialize options controller
     */
    public function __construct(): void
    {
        global $self, $vars, $cmdID, $pageID;
        $self->takeAll("components");
        if ($pageID !== 'rokuone') {
            $self->ses->takeAll('rokuone');
        }
        $self->ses->authenticate("webmaster");
    }

    /**
     * Display options page
     */
    public function index(): void
    {
        global $self, $doc, $vars, $cmdID;
        $self->gov2nav->setDefaultNavCustom();
        $role = isset($vars['role']) ? $vars['role'] : 'Options';
        $doc->body("pageTitle", ucwords($vars['app']) . " " . ucwords($role));

        match ($cmdID) {
            'setup' => $self->content("option_setup.html"),
            'view', 'view_services' => [
                $doc->body('view_type', $cmdID),
                $self->content("option_view.html"),
            ],
            'controlpanel' => $self->content("option_controlpanel.html"),
            default => null,
        };
    }
}
