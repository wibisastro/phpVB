<?php namespace App\home;

class status {
    function __construct () {
    }

    function index () {
        global $self,$doc;
        $self->take("components","gov2nav", "setDefaultNav");
        $doc->body("pageTitle",'Status Layanan');
        $doc->body("subTitle",'Ekosistem Gov3');
        $self->content();
    }
}
