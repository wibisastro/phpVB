<?php namespace App\home;
    
class apikey {
    function __construct () {
        global $doc;
        $doc->baseBody="index.html";
    }
    
    function index () {
        global $self;
        $self->content($_SERVER['SERVER_NAME'].".html");
    }
}