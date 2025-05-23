<?php namespace App\components;
    
class gov2notification {
    function __construct () {
        global $self,$doc;
		$self->take("components","gov2nav", "setDefaultNav");
        $doc->body("pageTitle",'Notification Component');
        $self->demo();
        $self->content('gov2notificationDemo.html');
        $self->content();
    }
}
