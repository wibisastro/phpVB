<?php namespace App\sakipai\model;

class index extends \Gov2lib\document {
	function __construct () {
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
		$this->controller=__DIR__."/../".$this->className.".php";
	}

    /**
     * Isi landing — satu-satunya tempat teks halaman. Sumber fakta:
     * sakipai:profil/PROJECT_CONTEXT.md (klien, mitra, pipeline 3-layer, target).
     * Tidak ada testimoni rekaan: slot "testimonials" template vendor dipakai
     * untuk "pihak di balik SAKIPAI".
     */
    function landingData (): array {
        return [
            'brand'     => 'SAKIPAI',
            'tagline'   => 'Evaluasi SAKIP Berbasis AI',
            'deskripsi' => 'Tools analisis dokumen dan evaluasi SAKIP dengan pendekatan AI-in-the-Loop: AI sebagai co-pilot asesor, bukan pengganti.',
            'masthead'  => [
                'judul'  => 'Evaluasi SAKIP lebih cepat dan konsisten, keputusan tetap di tangan asesor.',
                'teks'   => 'Dokumen kinerja ratusan halaman dibaca, dipetakan ke kriteria LKE AKIP 2026, dan diberi justifikasi berbasis kutipan. Asesor meninjau, bukan mengetik ulang.',
                'utama'  => ['label' => 'Masuk', 'url' => 'login'],
                'kedua'  => ['label' => 'Pelajari cara kerjanya', 'url' => '#fitur'],
            ],
            'fitur' => [
                ['ikon' => 'bi-folder-check', 'judul' => 'Kelengkapan dokumen',
                 'teks' => 'Audit dokumen wajib, penamaan kanonik, dan staging otomatis sebelum penilaian dimulai.'],
                ['ikon' => 'bi-search', 'judul' => 'Penilaian berbasis kutipan',
                 'teks' => 'Skor tiap kriteria LKE AKIP 2026 disertai justifikasi yang merujuk langsung ke dokumen sumber.'],
                ['ikon' => 'bi-person-check', 'judul' => 'Asesor tetap memutuskan',
                 'teks' => 'AI mempercepat pekerjaan repetitif dan administratif; keputusan akhir tetap milik asesor.'],
            ],
            'showcase' => [
                ['ikon' => 'bi-layers', 'judul' => 'Tiga lapis penilaian',
                 'teks' => 'Layer 0 kelengkapan dan ingest, Layer 1 evaluasi LKE, Layer 2 gating Minimum Requirement, lalu draf Laporan Hasil Evaluasi.'],
                ['ikon' => 'bi-journal-text', 'judul' => 'Jejak justifikasi',
                 'teks' => 'Setiap skor ditautkan ke kutipan dokumen sehingga mudah ditinjau, diperdebatkan, dan dipertanggungjawabkan.'],
                ['ikon' => 'bi-speedometer2', 'judul' => 'Dari dua minggu ke hitungan hari',
                 'teks' => 'Evaluasi manual memakan 10 sampai 14 hari per instansi. Target SAKIPAI 2 sampai 3 hari dengan hasil yang lebih seragam antar-asesor.'],
            ],
            'pihak' => [
                'judul' => 'Siapa di balik SAKIPAI',
                'daftar' => [
                    ['ikon' => 'bi-building', 'nama' => 'KemenPANRB',
                     'peran' => 'Pemilik kebijakan evaluasi SAKIP dan klien proyek.'],
                    ['ikon' => 'bi-mortarboard', 'nama' => 'UI-CSGAR',
                     'peran' => 'Mitra riset: Center for Study of Governance and Administrative Reform, Universitas Indonesia.'],
                    ['ikon' => 'bi-hdd-network', 'nama' => 'Platform gov3',
                     'peran' => 'Portal instansi berbasis phpVB tempat hasil evaluasi dibaca dan ditindaklanjuti.'],
                ],
            ],
            'ajakan' => [
                'judul'  => 'Siap mencoba? Masuk ke portal instansi Anda.',
                'tombol' => ['label' => 'Masuk', 'url' => 'login'],
            ],
            'footer' => [
                'tautan' => [
                    ['label' => 'Tentang', 'url' => '#fitur'],
                    ['label' => 'Cara kerja', 'url' => '#cara-kerja'],
                    ['label' => 'Kontak', 'url' => 'mailto'],
                ],
                'catatan' => 'Dibangun di atas platform gov3 (phpVB).',
            ],
        ];
    }

	function dependencies () {
	}
}
