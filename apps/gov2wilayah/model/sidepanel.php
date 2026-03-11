<?php

namespace App\gov2wilayah\model;

class sidepanel extends \Gov2lib\crudHandler
{
    public function __construct()
    {
        global $config, $doc;
        $this->templateDir = __DIR__ . '/../view';
        $path = explode('\\', __CLASS__);
        $this->className = $path[count($path) - 1];
        $this->controller = __DIR__ . '/../' . $this->className . '.php';
        parent::__construct($config->domain->attr['dsn'] ?? '');
        $this->tbl->table = $this->tbl->wilayah;
    }

    /**
     * Override doBrowse: filter by parent_id so only direct children are returned.
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
