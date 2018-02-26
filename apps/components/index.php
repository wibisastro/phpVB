<?php namespace App\components;
    
class index {
    function __construct () {
        global $self;
		$self->take("components","gov2nav", "setDefaultNav");      
    }
    
    function index () {
        global $self,$doc;
        $doc->body("pageTitle",'Components');
        $self->content();        
    }
}