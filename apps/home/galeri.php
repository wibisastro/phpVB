<?php namespace App\home;

class galeri {
    function __construct () {
    }

    function index () {
        global $self,$doc;
        $self->take("components","gov2nav", "setDefaultNav");
        $doc->body("pageTitle",'Galeri Komponen Cube');
        $doc->body("subTitle",'Panduan developer — telusuri komponen, salin ID untuk prompt/skill');

        // Baca index.json hasil build-galeri.php (docroot public/ = sibling apps/).
        $path = __DIR__.'/../../public/galeri/index.json';
        $data = is_file($path) ? json_decode(file_get_contents($path), true) : null;

        // Urutan tampil grup (developer flow: elemen kecil → halaman utuh).
        $order = ['Elemen UI','Form','Tabel & Data','Grafik','Halaman contoh','Halaman penuh'];
        $groups = [];
        if (is_array($data) && !empty($data['halaman'])) {
            foreach ($order as $g) $groups[$g] = [];
            foreach ($data['halaman'] as $h) {
                $g = $h['group'] ?? 'Elemen UI';
                $groups[$g][] = $h;
            }
            foreach ($groups as $g => $v) if (!$v) unset($groups[$g]);
        }

        $doc->body("galeriReady", $data ? 1 : 0);
        $doc->body("versi",     $data['versi'] ?? '');
        $doc->body("totalHal",  $data['total_halaman'] ?? 0);
        $doc->body("totalKomp", $data['total_komponen'] ?? 0);
        $doc->body("groups",    $groups);

        $self->content();
    }
}
