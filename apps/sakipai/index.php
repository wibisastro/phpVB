<?php namespace App\sakipai;

/**
 * Landing page SAKIPAI — jalur B pabrik tema (halaman publik, view standalone
 * per-app; rencana: bootstrap:rencana/pabrik-tema.md §6b).
 * Sengaja TANPA authenticate(): halaman terbuka tanpa login. Tanpa Vue.
 * Teks halaman hidup di model (landingData), bukan di view.
 */
class index {
    function __construct () {
    }

    function index () {
        global $self,$doc,$config;
        $landing = $self->landingData();
        $doc->body("pageTitle", $landing['brand'].' - '.$landing['tagline']);
        $doc->body("landing", $landing);
        $doc->body("kontakEmail", trim((string)($config->webmaster ?? '')));
        $doc->body("tahun", date('Y'));
        $self->content();
    }
}
