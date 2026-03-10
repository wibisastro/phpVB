<?php

namespace App\gov2instansi\model;

class index extends \Gov2lib\document
{
    public function __construct()
    {
        $this->templateDir = __DIR__ . '/../view';
        $path = explode('\\', __CLASS__);
        $this->className = $path[count($path) - 1];
        $this->controller = __DIR__ . '/../' . $this->className . '.php';
    }

    public function dependencies(): void
    {
    }
}
