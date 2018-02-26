<?php namespace App\examples;
    
class index {
    function __construct () {
        global $self,$doc;
		$self->take("components","gov2nav", "setDefaultNav");      
        $doc->body("pageTitle",'Examples');
        $self->content(); 
    }
}