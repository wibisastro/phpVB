<?php

namespace Gov2lib;

/**
 * CSS file server
 */
class css extends checkExist
{
    /**
     * Initialize CSS handler
     */
    public function __construct()
    {
        global $vars;
        $_app = $this->checkAppDir($vars["app"]);
        $this->baseName = $_app;
        $this->baseBody = 'bootstrapCssBody.html';
        $this->controller = __DIR__ . "/index.php";

        if (!isset($vars["style"])) {
            $vars["style"] = "";
        }

        $this->templateDir = __DIR__ . "/../../apps/" . $_app . "/css";
        $_style = $this->checkAppFile($_app . "/css", $vars["style"]);
        $this->componentName = $_style ?? '';
        header('Content-Type: text/css');
    }
}
