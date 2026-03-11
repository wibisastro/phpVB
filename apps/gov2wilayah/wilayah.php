<?php

namespace App\gov2wilayah;

class wilayah extends \Gov2lib\api
{
    public function __construct()
    {
        global $self, $doc;
        $self->takeAll('components');
        $doc->component('gov2option');
        parent::__construct();
        $self->scrollInterval = 100;
        $self->fields = $self->gov2formfield->getFields(__DIR__ . '/json/wilayah.json');
    }

    public function index(): void
    {
        global $self, $doc;
        $self->ses->authenticate('admin');
        $self->take('components', 'gov2nav', 'setDefaultNav');
        $doc->body('pageTitle', 'Wilayah');
        $doc->body('subTitle', 'Data Wilayah');
        $self->loadTable();
        $self->content();
    }

    public function fields(): array
    {
        global $self;
        return $self->fields;
    }

    public function breadcrumb(): mixed
    {
        global $self;
        return $self->getBreadcrumb('INDONESIA');
    }

    public function table(array $vars): mixed
    {
        global $self;
        return $self->getRecords($vars);
    }

    public function children(array $vars): array
    {
        global $self;
        return (array) $self->getChildren((int) ($vars['id'] ?? 0));
    }

    public function edit(array $vars): array
    {
        global $self;
        return $self->getRecord((int) ($vars['id'] ?? 0));
    }

    public function add(): array
    {
        global $self;
        unset($_POST['id'], $_POST['cmd'], $_POST['children'],
              $_POST['created_at']);

        $_id = $self->setRememberId(-1);
        if ($_id > 0) {
            $getParent = $self->getRecord($_id);
            if ($getParent['level_label'] === 'kabupaten') {
                $_POST['parent_id'] = $getParent['id'];
                $_POST['level'] = 3;
                $_POST['level_label'] = 'kecamatan';
                $_POST['kabupaten_id'] = $getParent['kabupaten_id'] ?? $getParent['id'];
            } else {
                $_POST['parent_id'] = $getParent['id'];
                $_POST['level'] = 4;
                $_POST['level_label'] = 'kelurahan';
                $_POST['kabupaten_id'] = $getParent['kabupaten_id'] ?? null;
                $_POST['kecamatan_id'] = $getParent['kecamatan_id'] ?? $getParent['id'];
            }
        } else {
            $_POST['parent_id'] = 0;
            $_POST['level'] = 1;
            $_POST['level_label'] = 'provinsi';
        }

        return $self->postAdd($_POST);
    }

    public function update(): array
    {
        global $self;
        return $self->postUpdate($_POST);
    }

    public function del(): array
    {
        global $self;
        return $self->postDel($_POST);
    }

    public function count(array $vars): mixed
    {
        global $self;
        return $self->getCount((int) ($vars['id'] ?? 0));
    }

    public function listWilayah(array $vars): mixed
    {
        global $self;
        return $self->getRecords($vars);
    }
}
