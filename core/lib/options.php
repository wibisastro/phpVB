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
    public function __construct()
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
        global $self, $doc, $vars, $cmdID, $scriptID;
        $self->gov2nav->setAutoNav();
        $role = isset($vars['role']) ? $vars['role'] : 'Options';
        $doc->body("pageTitle", ucwords($vars['app']) . " " . ucwords($role));

        // Flavor setup dibawa ke iframe dalam via segmen privilege
        // (setup vs setup_services) — daftar cluster difilter per type
        $doc->body('setupPath', $scriptID === 'services' ? 'setup_services' : 'setup');

        match ($cmdID) {
            'setup' => $self->content("option_setup.html"),
            'view' => [
                $doc->body('view_type', $scriptID === 'services' ? 'view_services' : 'view'),
                $self->content("option_view.html"),
            ],
            'controlpanel' => $self->content("option_controlpanel.html"),
            default => null,
        };
    }
}
