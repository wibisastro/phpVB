<?php namespace App\home;
    
class error {
    function __construct () {
        global $doc;
//        $doc->baseBody="error.html";
    }
    
    function index () {
        global $self,$doc;
        $doc->body("pageTitle",'Something Went Wrong...');
//        $self->content();
    }
}