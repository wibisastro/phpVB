<?php

namespace App\gov2pilot\model;

/**
 * Model pilot fase T3 #6085 — crudHandler di atas driver supabase.
 *
 * DSN app ini (apps/gov2pilot/xml/dsnSource.{stage}.xml, gitignored)
 * memakai <driver>supabase</driver> + <url> + <key>; contoh:
 *
 *   <?xml version="1.0" encoding="UTF-8"?>
 *   <list>
 *     <dsn>
 *       <name>master</name>
 *       <driver>supabase</driver>
 *       <url>https://gajah.gov3.id</url>
 *       <key>eyJ... (anon = read-only; service_role = tulis penuh)</key>
 *     </dsn>
 *   </list>
 *
 * doAdd/doUpdate DIWARISI apa adanya dari crudModel: jalurnya
 * (columnList + insert + update "id=%i") sudah terpetakan penuh ke
 * PostgREST oleh SupabaseAdapter lewat db(). Method berbasis raw SELECT
 * (doRead/doBrowse/doCountChildren/doDel) di-override ke repo() —
 * pola repository-level yang dianjurkan untuk app baru (keputusan T0).
 */
class index extends \Gov2lib\crudHandler
{
    function __construct()
    {
        global $config;
        $this->templateDir = __DIR__ . "/../view";
        $path = explode("\\", __CLASS__);
        $this->className = $path[sizeof($path) - 1];
        $this->controller = __DIR__ . "/../" . $this->className . ".php";
        parent::__construct(trim((string) ($config->domain->attr['dsn'] ?? '')));
    }

    function loadTable(): void
    {
        //---gov2pagination
        $GLOBALS['vueData']['geturl'] = '/gov2pilot';
        $GLOBALS['vueData']['itemPerPage'] = 50;
        $GLOBALS['vueData']['interval'] = array(50, 100, 200, 300);
        $GLOBALS['vueData']['scrollInterval'] = $this->scrollInterval;
        //---gov2formfield
        $GLOBALS['vueData']['fieldurl'] = $this->className . '/fields';
    }

    public function doRead(int $id = 0): ?array
    {
        return $this->repo()->read((string) $this->tbl->table, $id);
    }

    public function doBrowse(int|string $scroll = 0, int|string $parentId = 0, string $parentIdName = ''): ?array
    {
        $interval = $this->scrollInterval ?: 1000;
        $offset = max(0, (int) $scroll - 1) * $interval;

        return $this->repo()->browse((string) $this->tbl->table, [], $interval, $offset, 'id DESC');
    }

    public function doCountChildren(int|string $parentId = 0): ?array
    {
        return ['totalRecord' => $this->repo()->count((string) $this->tbl->table)];
    }

    public function doDel(int $id = 0): void
    {
        $this->repo()->delete((string) $this->tbl->table, $id);
    }

    function dependencies () {
    }
}
