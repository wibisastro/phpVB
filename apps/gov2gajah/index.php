<?php namespace App\gov2gajah;

/**
 * Contoh app tier 3 #6085 (dulu gov2pilot) — CRUD via SupabaseAdapter (gajah).
 *
 * Halaman CRUD standar (pola apps/home/crud.php); bedanya seluruh data
 * hidup di tabel public.phpvb_pilot_todo di gajah dan diakses lewat
 * PostgREST, tanpa koneksi database langsung. Model tidak meng-override
 * apa pun — driver dipilih murni dari tag <driver> di DSN XML (T4).
 */
class index extends \Gov2lib\api {

    function __construct() {
        global $self;
        $self->takeAll("components");
        parent::__construct();
        $self->scrollInterval=300;
        $self->fields = $self->gov2formfield->getFields(__DIR__ . "/json/index.json");
    }

    function index() {
        global $self,$doc;
        $self->take("components","gov2nav", "setDefaultNav");
        $doc->body("pageTitle",'Contoh Tier 3 — Supabase (gajah)');
        $doc->body("subTitle",'Daftar Tugas (data di gajah via PostgREST)');
        $self->loadTable();
        $self->gov2notification->content();
        $self->gov2search->content();
        $self->gov2button->content();
        $self->content();
        $self->gov2pagination->content();
    }

    function fields () {
        global $self;
        return $self->fields;
    }

    function edit ($vars) {
        global $self;
        return $self->getRecord((int) $vars['id']);
    }

    function count ($vars) {
        global $self;
        return $self->getCount((int) ($vars['id'] ?? 0));
    }

    function table ($vars) {
        global $self;
        return $self->getRecords($vars);
    }

    function add () {
        global $self;
        unset($_POST['id']);
        return $self->postAdd($_POST);
    }

    function update () {
        global $self;
        return $self->postUpdate($_POST);
    }

    function del () {
        global $self;
        return $self->postDel($_POST);
    }
}
