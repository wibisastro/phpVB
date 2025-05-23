<?php namespace App\home;
    
class html {
    function __construct () {
        global $doc;
        $doc->baseBody="index.html";
    }
    
    function index () {
        global $self,$htmlFile;
        $self->content($htmlFile);
    }
}