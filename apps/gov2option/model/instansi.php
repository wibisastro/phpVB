<?php

namespace App\gov2option\model;

class instansi extends \Gov2lib\crudHandler
{
    public function __construct()
    {
        global $config, $doc;
        $this->templateDir = __DIR__ . '/../view';
        $path = explode('\\', __CLASS__);
        $this->className = $path[count($path) - 1];
        $doc->body('className', $this->className);
        $this->controller = __DIR__ . '/../' . $this->className . '.php';
        parent::__construct($config->domain->attr['dsn'] ?? '');
        $this->tbl->table = $this->tbl->kementerian;
    }

    public function loadTable(): void
    {
        $GLOBALS['vueData']['itemPerPage'] = 10;
        $GLOBALS['vueData']['interval'] = [10, 25, 50, 100];
        $GLOBALS['vueData']['scrollInterval'] = $this->scrollInterval;
        $GLOBALS['vueData']['fieldurl'] = $this->className . '/fields';
    }

    public function getInstansi(): ?array
    {
        $result = null;
        try {
            $query = "SELECT * FROM {$this->tbl->table} WHERE level_label='eselon2' ORDER BY kode ASC";
            $result = \DB::query($query);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }
        return $result;
    }

    public function dependencies(): void
    {
    }
}
