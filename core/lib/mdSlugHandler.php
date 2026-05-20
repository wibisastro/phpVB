<?php

namespace Gov2lib;

/**
 * Generic MD-by-slug handler.
 *
 * Serves URL `/{app}/{controller}/md/{folder}/{slug}` by rendering
 * `apps/{app}/md/{tenant}/{folder}/{slug}.md` (with tenant fallback to
 * generic). Lets any controller expose static markdown content without
 * per-controller method or route XML boilerplate.
 *
 * Pairs with controller class Gov2lib\mdslug (dispatched via switch
 * case in core/init/route.php).
 */
class mdSlugHandler extends checkExist
{
    public function __construct()
    {
        global $vars, $config;
        parent::__construct($config->domain->attr['dsn']);
        $_app = $this->checkAppDir($vars["app"] ?? '');
        $this->templateDir = __DIR__ . "/../../apps/" . $_app . "/view";
        $this->controller = __DIR__ . "/mdslug.php";
    }
}
