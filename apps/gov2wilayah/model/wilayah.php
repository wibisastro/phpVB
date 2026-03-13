<?php

namespace App\gov2wilayah\model;

class wilayah extends \Gov2lib\crudHandler
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
        $this->tbl->table = $this->tbl->wilayah;
    }

    public function loadTable(): void
    {
        global $doc;
        $prefix = '/' . $doc->pageID . '/' . $this->className;
        $GLOBALS['vueData']['action'] = $prefix;
        $GLOBALS['vueData']['fieldurl'] = $prefix . '/fields';
        $GLOBALS['vueData']['breadcrumburl'] = $prefix . '/breadcrumb';
        $GLOBALS['vueData']['itemPerPage'] = 10;
        $GLOBALS['vueData']['interval'] = [10, 25, 50, 100];
        $GLOBALS['vueData']['scrollInterval'] = $this->scrollInterval;
    }

    /**
     * Override doBrowse: filter by parent_id and add children count.
     */
    public function doBrowse(int|string $scroll = 0, int|string $parentId = 0, string $parentIdName = ''): ?array
    {
        try {
            $scrolled = $this->scroll((int) $scroll);
            $query = "SELECT * FROM {$this->tbl->table} WHERE parent_id=%i ORDER BY nama ASC LIMIT {$scrolled}";
            $results = \DB::query($query, (int) $parentId);

            foreach ($results as $i => $row) {
                $count = \DB::queryFirstField(
                    "SELECT COUNT(*) FROM {$this->tbl->table} WHERE parent_id=%i",
                    $row['id']
                );
                $results[$i]['children'] = (int) $count;
            }

            return $results;
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
            return null;
        }
    }

    /**
     * Override doCountChildren: count by parent_id.
     */
    public function doCountChildren(int|string $parentId = 0): ?array
    {
        $query = "SELECT count(id) as totalRecord FROM {$this->tbl->table} WHERE parent_id=%i";
        return \DB::queryFirstRow($query, (int) $parentId);
    }

    public function dependencies(): void
    {
    }
}
