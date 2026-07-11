<?php

namespace App\gov2gajah\model;

/**
 * Contoh app tier 3 (#6085, dulu bernama gov2pilot) — crudHandler di atas
 * driver supabase (gajah).
 *
 * DSN app ini (apps/gov2gajah/xml/dsnSource.{stage}.xml, gitignored)
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
 * Sejak T4, model ini TIDAK meng-override method CRUD apa pun: seluruh
 * doAdd/doRead/doUpdate/doDel/doBrowse/doCountChildren diwarisi dari
 * crudModel yang driver-branch — ganti tier cukup dengan mengubah tag
 * <driver> di DSN XML (supabase ↔ meekro), model tak perlu disentuh.
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
        $GLOBALS['vueData']['geturl'] = '/gov2gajah';
        $GLOBALS['vueData']['itemPerPage'] = 50;
        $GLOBALS['vueData']['interval'] = array(50, 100, 200, 300);
        $GLOBALS['vueData']['scrollInterval'] = $this->scrollInterval;
        //---gov2formfield
        $GLOBALS['vueData']['fieldurl'] = $this->className . '/fields';
    }

    function dependencies () {
    }
}
