<?php

namespace Gov2lib;

/**
 * Survey handler
 */
class surveyHandler extends checkExist
{
    /**
     * Initialize survey handler
     */
    public function __construct(): void
    {
        global $vars, $config;
        parent::__construct($config->domain->attr['dsn']);
        $_app = $this->checkAppDir($vars["app"]);
        $this->templateDir = __DIR__ . "/../../apps/" . $_app . "/view";
        $this->controller = __DIR__ . "/survey.php";
    }
}
