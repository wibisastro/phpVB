<?php

namespace App\gov2instansi;

class instansi extends \Gov2lib\api
{
    public function __construct()
    {
        global $self, $doc;
        $self->takeAll('components');
        $doc->component('gov2option');
        parent::__construct();
        $self->scrollInterval = 100;
        $self->fields = $self->gov2formfield->getFields(__DIR__ . '/json/instansi.json');
    }

    public function index(): void
    {
        global $self, $doc;
        $self->ses->authenticate('admin');
        $self->take('components', 'gov2nav', 'setDefaultNav');
        $doc->body('pageTitle', 'Instansi');
        $doc->body('subTitle', 'Data Instansi');
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
        return $self->getBreadcrumb('Instansi');
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
              $_POST['created_at'], $_POST['modify_at'], $_POST['modify_by']);
        $_POST['created_by'] = $self->ses->val['account_id'] ?? 0;

        $_id = $self->setRememberId(-1);
        if ($_id > 0) {
            $_POST['parent_id'] = $_id;
            $_POST['level'] = 2;
            $_POST['level_label'] = 'eselon2';
        } else {
            $_POST['parent_id'] = 0;
            $_POST['level'] = 1;
            $_POST['level_label'] = 'eselon1';
        }

        return $self->postAdd($_POST);
    }

    public function update(): array
    {
        global $self;
        unset($_POST['modify_at']);
        $_POST['modify_by'] = $self->ses->val['account_id'] ?? 0;
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

    public function getInstansi(): mixed
    {
        global $self, $doc;
        $data = $self->getInstansi();
        return $doc->responseGet($data);
    }
}
