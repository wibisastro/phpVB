<?php

namespace Gov2lib;

/**
 * Vue file server
 */
class vue extends checkExist
{
    /**
     * Initialize Vue handler
     */
    public function __construct()
    {
        global $vars;
        $_app = $this->checkAppDir($vars["app"]);
        $this->baseName = $_app;
        $this->baseBody = 'jsBody.html';

        if (!isset($vars["component"])) {
            $vars["component"] = "";
        }

        $this->templateDir = __DIR__ . "/../../apps/" . $_app . "/vue";
        $_component = $this->checkAppFile($_app . "/vue", $vars["component"]);
        $this->componentName = $_component;
    }
}
