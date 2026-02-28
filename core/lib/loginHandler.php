<?php

namespace Gov2lib;

/**
 * Login handler
 */
class loginHandler extends checkExist
{
    /**
     * Initialize login handler
     */
    public function __construct()
    {
        global $vars, $config, $scriptID;
        parent::__construct($config->domain->attr['dsn']);
        $_app = $this->checkAppDir($vars["app"]);
        $this->templateDir = __DIR__ . "/../../apps/" . $_app . "/view";
        $this->controller = __DIR__ . "/login.php";
    }
}
