<?php namespace App\home;

class undercon {
    function __construct () {
    }

    function index () {
        global $self,$doc;
        $self->take("components","gov2nav", "setDefaultNav");
        $doc->body("pageTitle",'Sedang Dibangun');
        $doc->body("readMD",'undercon');
        $doc->body("ref", $_GET['ref'] ?? '');
        $self->content();
    }
}
