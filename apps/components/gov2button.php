<?php namespace App\components;
    
class gov2button {
    function __construct () {
        global $self,$doc;
		$self->take("components","gov2nav", "setDefaultNav");
        
        $doc->body("pageTitle",'Button Component');
        //$api->sendsession();
        $self->content();
        $self->content('gov2buttonDemo.html');  
    }
}