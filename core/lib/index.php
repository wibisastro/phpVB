<?php namespace Gov2lib;

class index {
    function __construct () {
        
    }
    
    function index () {
        global $self;
        if (file_exists($self->templateDir."/".$self->componentName)) {
            readfile($self->templateDir."/".$self->componentName);
            exit;
        } else {echo "NotExist";}        
    }
}