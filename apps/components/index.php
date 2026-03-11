<?php namespace App\components;
    
class index {
    function __construct () {
        global $self;
		$self->take("components","gov2nav", "setDefaultNav");      
    }
    
    function index () {
        global $self,$doc;
        $doc->body("pageTitle",'Components');

        $readmePath = __DIR__ . '/README.md';
        if (file_exists($readmePath)) {
            $doc->body("readmeHtml", \Gov2lib\markdown::renderFile($readmePath));
            $self->content();
        } else {
            $doc->baseBody = "error404.html";
        }
    }
}