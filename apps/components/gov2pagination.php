<?php namespace App\components;
    
class gov2pagination {
    function __construct () {
        global $self,$doc;
		$self->take("components","gov2nav", "setDefaultNav");
        $doc->body("pageTitle",'Pagination Component');
        $self->content();
        $self->demo();
    }
}