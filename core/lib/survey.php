<?php

namespace Gov2lib;

/**
 * Survey controller
 */
class survey
{
    /**
     * Initialize survey controller
     */
    public function __construct()
    {
        global $self, $vars, $cmdID;
        $self->takeAll("components");
        $self->takeAll("rokuone");
        $self->ses->authenticate("webmaster");
    }

    /**
     * Display survey page
     */
    public function index(): void
    {
        global $self, $doc, $vars, $cmdID;
        $self->gov2nav->setDefaultNavCustom();
        $role = isset($vars['action']) ? $vars['action'] : 'Setup';
        $doc->body("pageTitle", strtoupper($vars['app']) . " " . ucwords($role));

        match ($cmdID) {
            'setup' => [
                $doc->body("pageTitle", strtoupper($vars['app']) . " " . 'Kuesioner'),
                $self->content("survey_setup.html"),
            ],
            'view' => [
                $doc->body("pageTitle", strtoupper($vars['app']) . " " . 'Survey'),
                $self->content("survey_view.html"),
            ],
            'result' => [
                $doc->body("pageTitle", strtoupper($vars['app']) . " " . 'Hasil Survey'),
                $self->content("survey_result.html"),
            ],
            default => [
                $doc->body("pageTitle", 'Page Not Found'),
                $self->content('error404.html'),
            ],
        };
    }
}
