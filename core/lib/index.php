<?php

namespace Gov2lib;

/**
 * Index controller for serving static files
 */
class index
{
    /**
     * Initialize index controller
     */
    public function __construct()
    {
    }

    /**
     * Serve file with CORS headers
     */
    public function index(): void
    {
        global $self;

        if (file_exists($self->templateDir . "/" . $self->componentName)) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
            readfile($self->templateDir . "/" . $self->componentName);
            exit;
        } else {
            echo "NotExist";
        }
    }
}
