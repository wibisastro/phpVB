<?php namespace App\home;

class status {
    function __construct () {
        // R0 role-framework: keputusan Wibi 20 Jul — apps/home ber-gate guest.
        global $self;
        $self->ses->authenticate('guest');
    }

    function index () {
        global $self,$doc;
        $self->take("components","gov2nav", "setDefaultNav");
        $doc->body("pageTitle",'Status Layanan');
        $doc->body("subTitle",'Ekosistem Gov3');
        $self->content();
    }
}
