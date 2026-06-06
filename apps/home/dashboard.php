<?php namespace App\home;

/**
 * Controller dashboard portal landing (app home) — Lebah #5210.
 *
 *   index() → halaman dashboard (Info Diri + Agregat), assets via externalJS.
 *   json()  → endpoint data live, dipanggil axios dari dashboardAssets.html.
 */
class dashboard {

    function index () {
        global $self,$doc;
        $self->take("components","gov2nav", "setDefaultNav");
        $self->take("components","gov2nav", "setDefaultNav", "dashboard.xml");
        $doc->body("pageTitle",'Dashboard');
        // CSS+JS render taruh di luar #app supaya Vue 2 tak strip <style>/<script>.
        $self->externalJS('dashboardAssets.html');
        $doc->body("readMD",'dashboard');
        $self->content();
    }

    function json () {
        global $self;
        // echo + exit: selalu balas JSON apa pun header Accept klien (robust;
        // tak bergantung pada cabang page/ajax di public/index.php).
        header('Content-Type: application/json');
        echo json_encode($self->getKelengkapan());
        exit;
    }
}
