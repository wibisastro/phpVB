<?php

namespace Gov2lib;

/**
 * Role handler
 */
class roleHandler extends checkExist
{
    /**
     * Initialize role handler
     */
    public function __construct()
    {
        global $vars, $config;
        parent::__construct($config->domain->attr['dsn']);
        $_app = $this->checkAppDir($vars["app"]);
        $this->templateDir = __DIR__ . "/../../apps/" . $_app . "/view";
        $this->controller = __DIR__ . "/role.php";
    }
}
