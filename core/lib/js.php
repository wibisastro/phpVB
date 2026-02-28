<?php

namespace Gov2lib;

/**
 * JavaScript file server
 */
class js extends checkExist
{
    /**
     * Initialize JavaScript handler
     */
    public function __construct(): void
    {
        global $vars;
        $_app = $this->checkAppDir($vars["app"]);
        $this->baseName = $_app;
        $this->baseBody = 'jsBody.html';
        $this->controller = __DIR__ . "/index.php";

        if (!isset($vars["component"])) {
            $vars["component"] = "";
        }

        $this->templateDir = __DIR__ . "/../../apps/" . $_app . "/js";
        $_component = $this->checkAppFile($_app . "/js", $vars["component"]);
        $this->componentName = $_component;
        header('Content-Type: application/javascript');
    }
}
