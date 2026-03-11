<?php

namespace App\gov2wilayah;

class index
{
    public function __construct()
    {
    }

    public function index(): void
    {
        global $self, $doc;
        $self->take('components', 'gov2nav', 'setDefaultNav');
        $doc->body('pageTitle', 'Wilayah');
        $doc->body('subTitle', 'Manajemen Data Wilayah');

        $readmePath = __DIR__ . '/README.md';
        if (file_exists($readmePath)) {
            $doc->body('readmeHtml', \Gov2lib\markdown::renderFile($readmePath));
            $self->content();
        } else {
            $doc->baseBody = 'error404.html';
        }
    }
}
