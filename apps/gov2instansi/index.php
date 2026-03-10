<?php

namespace App\gov2instansi;

class index
{
    public function __construct()
    {
    }

    public function index(): void
    {
        global $self, $doc;
        $self->take('components', 'gov2nav', 'setDefaultNav', 'menu.xml');
        $doc->body('pageTitle', 'Instansi');
        $doc->body('subTitle', 'Manajemen Data Instansi');
        $self->content();
    }
}
