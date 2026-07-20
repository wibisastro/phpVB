<?php namespace App\home;

class grafik {
    function __construct () {
        // R0 role-framework: keputusan Wibi 20 Jul — apps/home ber-gate guest.
        global $self;
        $self->ses->authenticate('guest');
    }

    function index () {
        global $self,$doc;
        $self->take("components","gov2nav", "setDefaultNav");
        $doc->body("pageTitle",'Grafik');
        $doc->body("subTitle",'Contoh ApexCharts (komponen Cube apex#6 + apex#3)');
        // Vendor apex + init taruh di luar #app (externalJS) supaya loader Vue
        // tak strip <script>. Pola sama dgn dashboard (Lebah #5210).
        $self->externalJS('grafikAssets.html');
        $self->content();
    }
}
