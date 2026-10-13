<?php namespace App\sakipai;

/**
 * Landing page SAKIPAI (K9, 13 Okt 2026) — /sakipai/landing, nantinya di-host di kelinci dengan domain
 * sakip.ai.id. Memakai TEMA halaman publik `sarab` (core/template/sarab + public/sarab) lewat body.html
 * app yang meng-extend tema sesuai variabel `tema`; palet merah vendor dikulit-ulang ke ungu gov3 oleh
 * apps/sakipai/css/landing.css. Isi dari model (landingData, sumber: proyek sakipai). Tanpa authenticate().
 * MVC index (/sakipai) tetap memakai tema landing-page.
 */
class landing {
    function __construct () {
    }

    function index () {
        global $self,$doc,$config;
        $landing = $self->landingData();
        $doc->body("tema", "sarab");
        $doc->body("landingCss", "landing.css");
        $doc->body("pageTitle", $landing['brand'].$landing['brandAksen'].' - '.$landing['tagline']);
        $doc->body("landing", $landing);
        $doc->body("kontakEmail", trim((string)($config->webmaster ?? '')));
        $doc->body("tahun", date('Y'));
        $self->content();
    }
}
