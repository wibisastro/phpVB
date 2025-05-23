<?php namespace Gov2lib;

class index {
    function __construct () {

    }
    
    function index () {
        global $self;
        if (file_exists($self->templateDir."/".$self->componentName)) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
            readfile($self->templateDir."/".$self->componentName);
            exit;
        } else {echo "NotExist";}        
    }
}