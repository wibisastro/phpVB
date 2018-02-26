<?php namespace App\components;
    
class gov2search {
    function __construct () {
        global $self,$doc;
        $doc->body("pageTitle",'Search Component');
		$self->take("components","gov2nav", "setDefaultNav");
        $self->content();
        $self->content('gov2searchDemo.html');
    }
}