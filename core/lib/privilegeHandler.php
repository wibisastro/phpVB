<?php

namespace Gov2lib;

/**
 * Privilege handler
 */
class privilegeHandler extends checkExist
{
    /**
     * Initialize privilege handler
     */
    public function __construct()
    {
        global $vars, $config;
        parent::__construct($config->domain->attr['dsn']);
        $_app = $this->checkAppDir($vars["app"]);
        $this->templateDir = __DIR__ . "/../../apps/" . $_app . "/view";
        $this->controller = __DIR__ . "/privilege.php";
    }
}
