<?php namespace App\sakipai;

/**
 * Landing page SAKIPAI — memakai TEMA halaman publik `landing-page`
 * (core/template/landing-page + public/landing-page; rencana bootstrap:rencana/pabrik-tema.md).
 * App hanya: rute + controller ini + model (teks, landingData) + view/body.html yang
 * extends "@landing-page/layout.html". Sengaja TANPA authenticate(): terbuka tanpa login. Tanpa Vue.
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
