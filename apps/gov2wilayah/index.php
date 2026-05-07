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
        $doc->body('readMD');
        $self->content();
    }
}
