<?php namespace App\sakipai\model;

class landing extends \Gov2lib\document {
	function __construct () {
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
		$this->controller=__DIR__."/../".$this->className.".php";
	}

    /**
     * Isi landing SAKIPAI — SATU-SATUNYA tempat teks halaman; view hanya menyusun (kontrak variabel tema
     * sarab: bootstrap:hasil/tema/sarab/SUMBER.md). Sumber fakta: sakipai:profil/PROJECT_CONTEXT.md,
     * referensi/konsep/arsitektur_pipeline_aisakip.md, rencana/laporan_poc_workload_komputasi_sakipai.md
     * (per 13 Okt 2026). Tidak ada testimoni/kutipan rekaan (testimoni = null); angka hanya yang ada di
     * dokumen proyek. Gambar = SVG milik app (apps/sakipai/img, rute inti /sakipai/img/{file}).
     * Anchor = id section tema sarab: #hero #about #menu #chefs #reservation #contact-section.
     */
    function landingData (): array {
        global $config;
        $img = rtrim((string)($config->webroot ?? ''), '/').'/sakipai/img';
        return [
            'brand'      => 'SAKIP',
            'brandAksen' => 'AI',
            'brandIkon'  => 'bi-robot',
            'tagline'    => 'Evaluasi SAKIP berbasis AI',
            'deskripsi'  => 'Tools analisis dokumen dan evaluasi SAKIP dengan pendekatan AI-in-the-Loop: AI sebagai co-pilot asesor, bukan pengganti. Klien KemenPANRB, mitra riset UI-CSGAR.',
            'topbarTag'  => 'AI-in-the-Loop · piloting 2026',
            'kontak' => [   // alamat/telepon tidak dicantumkan di dokumen proyek → kosong (section menyembunyikannya)
                'alamat' => '', 'alamatPanjang' => '', 'telepon' => '', 'jam' => '',
            ],
            'sosmed' => [],   // belum ada akun resmi
            'menu' => [
                ['label' => 'Beranda',    'url' => '#hero'],
                ['label' => 'Tentang',    'url' => '#about'],
                ['label' => 'Cara kerja', 'url' => '#menu'],
                ['label' => 'Pihak',      'url' => '#chefs'],
                ['label' => 'Piloting',   'url' => '#reservation'],
                ['label' => 'Kontak',     'url' => '#contact-section'],
            ],
            'navCta' => ['label' => 'Masuk', 'url' => 'login', 'ikon' => 'bi-box-arrow-in-right'],
            'hero' => [
                'badge'       => 'Klien KemenPANRB · mitra riset UI-CSGAR',
                'judul'       => 'Evaluasi SAKIP',
                'judulAksen'  => 'lebih cepat,',
                'judulLanjut' => 'keputusan tetap di tangan asesor',
                'teks'        => 'Dokumen kinerja ratusan halaman dibaca, dipetakan ke kriteria LKE AKIP 2026, dan diberi justifikasi berbasis kutipan. Asesor meninjau dan memutuskan, bukan mengetik ulang.',
                'utama'       => ['label' => 'Lihat cara kerja', 'url' => '#menu', 'ikon' => 'bi-diagram-3'],
                'video'       => null,
                'statistik'   => [
                    ['angka' => '640', 'sufiks' => '',  'label' => 'portal instansi'],
                    ['angka' => '119', 'sufiks' => '',  'label' => 'kriteria LKE 2026 (65 Pusat + 54 Unit)'],
                    ['angka' => '45',  'sufiks' => '',  'label' => 'kriteria Min Req'],
                    ['angka' => '7',   'sufiks' => '',  'label' => 'langkah pipeline'],
                ],
                'gambar'    => $img.'/landing-hero.svg',
                'latarTeks' => 'SAKIP',
                'kartu' => [
                    ['ikon' => 'bi-layers',       'warna' => 'r', 'judul' => '3 lapis + LHE',     'teks' => 'Layer 0 · 1 · 2'],
                    ['ikon' => 'bi-quote',        'warna' => 'y', 'judul' => 'Berbasis kutipan',  'teks' => 'tiap skor punya rujukan'],
                    ['ikon' => 'bi-person-check', 'warna' => 'g', 'judul' => 'Asesor memutuskan', 'teks' => 'AI-in-the-Loop'],
                ],
            ],
            'marquee' => ['LKE AKIP 2026 v3.1', 'PermenPANRB 88/2021', 'AI-in-the-Loop', 'RAG per instansi', 'Audit kelengkapan', 'Min Req gating', 'LHE multi-gaya', 'KMS Lebah per instansi'],
            'kategori' => [
                'kicker' => 'Cara kerja', 'judul' => 'Tiga lapis', 'judulAksen' => '+ LHE',
                'teks'   => 'Konvensi internal SAKIPAI: tahap bergerbang mekanis (gating atau scoring) disebut layer; LHE adalah keluaran, bukan layer.',
                'semua'  => 'Semua langkah', 'satuan' => 'langkah',
                'daftar' => [
                    ['kode' => 'l0',  'nama' => 'Layer 0 · Kelengkapan & ingest', 'ikon' => 'bi-folder-check',      'gambar' => ''],
                    ['kode' => 'l1',  'nama' => 'Layer 1 · LKE',                  'ikon' => 'bi-clipboard-check',   'gambar' => ''],
                    ['kode' => 'l2',  'nama' => 'Layer 2 · Min Req',              'ikon' => 'bi-shield-check',      'gambar' => ''],
                    ['kode' => 'lhe', 'nama' => 'LHE',                            'ikon' => 'bi-file-earmark-text', 'gambar' => ''],
                ],
            ],
            'tentang' => [
                'kicker' => 'Tentang SAKIPAI', 'judul' => 'AI mempercepat,', 'judulAksen' => 'asesor memutuskan',
                'teks'   => 'Evaluasi SAKIP manual memakan 10 sampai 14 hari per instansi untuk ratusan halaman dokumen, rentan bias antar-asesor, dan sekitar 70 % pekerjaannya repetitif dan administratif. SAKIPAI mengotomatiskan bagian itu; setiap skor draf AI melewati Tinjauan Asesor sebelum menjadi LHE final.',
                'angka'  => ['nilai' => '2–3', 'label' => 'hari per instansi (target, dari 2 minggu)'],
                'gambar' => $img.'/landing-tentang.svg', 'ikon' => 'bi-building',
                'gambarKecil' => $img.'/landing-tentang-kecil.svg',
                'fitur' => [
                    ['ikon' => 'bi-folder-check', 'judul' => 'Kelengkapan dokumen lebih dulu',
                     'teks' => 'Audit dokumen wajib, penamaan kanonik, dan staging otomatis sebelum penilaian dimulai; scope yang belum layak diblokir tanpa menahan scope lain.'],
                    ['ikon' => 'bi-quote',        'judul' => 'Penilaian berbasis kutipan',
                     'teks' => 'Skor tiap kriteria LKE AKIP 2026 disertai justifikasi yang merujuk langsung ke dokumen sumber, sehingga mudah ditinjau dan dipertanggungjawabkan.'],
                    ['ikon' => 'bi-person-check', 'judul' => 'Asesor tetap memutuskan',
                     'teks' => 'Predikat dan LHE hanya terbit setelah Tinjauan Asesor; AI mengerjakan yang repetitif, keputusan tetap milik manusia.'],
                ],
                'tombol' => ['label' => 'Lihat cara kerja', 'url' => '#menu', 'ikon' => 'bi-diagram-3'],
            ],
            'produk' => [
                'kicker' => 'Pipeline 7 langkah', 'judul' => 'Dari dokumen ke', 'judulAksen' => 'Laporan Hasil Evaluasi',
                'daftar' => [
                    ['kode' => 'l0', 'kategori' => 'Layer 0', 'judul' => 'Audit kelengkapan', 'ikon' => 'bi-folder-check',
                     'teks' => 'Pindai dokumen instansi, klasifikasi otomatis, penamaan kanonik, mirror ke staging.',
                     'deskripsi' => 'Memindai unggahan apa pun tata letaknya, mengklasifikasi jenis/tahun/status dari nama berkas, mencocokkan dengan daftar dokumen wajib (Surat KemenPANRB B/103/2025), lalu menyalin yang lolos ke staging kanonik. Status LENGKAP ≥ 90 %, CUKUP 70–89 %, KURANG 50–69 %, TIDAK LAYAK < 50 % — per scope independen.',
                     'harga' => 'E0 → E1', 'hargaLama' => '', 'keterangan' => '/sakip_lyr0-kelengkapan',
                     'badge' => ['teks' => 'Layer 0', 'gaya' => ''], 'tag' => ['docget → docput', 'per scope'], 'gambar' => ''],
                    ['kode' => 'l0', 'kategori' => 'Layer 0', 'judul' => 'Ingest & RAG', 'ikon' => 'bi-database-check',
                     'teks' => 'Parse dokumen, potong per bagian, embed, simpan ke RAG per instansi.',
                     'deskripsi' => 'Dokumen kanonik diurai DocEngine (Docling + HybridChunker), di-embed BGE-M3, dan disimpan di pgvector gajah.gov3.id sebagai RAG per instansi; berkas besar dipecah otomatis, dokumen multi-buku tetap satu dokumen logis.',
                     'harga' => 'E1', 'hargaLama' => '', 'keterangan' => '/sakip_lyr0-ingest',
                     'badge' => ['teks' => 'Layer 0', 'gaya' => ''], 'tag' => ['DocEngine', 'BGE-M3', 'pgvector'], 'gambar' => ''],
                    ['kode' => 'l1', 'kategori' => 'Layer 1', 'judul' => 'Evaluasi LKE', 'ikon' => 'bi-clipboard-check',
                     'teks' => 'Skor 65 kriteria Pusat / 54 kriteria Unit LKE AKIP 2026 + justifikasi berkutipan.',
                     'deskripsi' => 'Tiap kriteria dinilai dengan prompt per kriteria (1 906 baris, tersusun per bagian kanonik), jawaban Ya/Tidak atau A/B/C/D dikonversi ke nilai lewat tabel fraksi, bobot komponen 30/30/15/25. Setiap skor ditautkan ke kutipan dokumen sumber.',
                     'harga' => 'E1 → E2', 'hargaLama' => '', 'keterangan' => '/sakip_lyr1-evaluasi',
                     'badge' => ['teks' => 'Layer 1', 'gaya' => 'new'], 'tag' => ['65 + 54 kriteria', 'kutipan'], 'gambar' => ''],
                    ['kode' => 'l1', 'kategori' => 'Layer 1', 'judul' => 'Publikasi ke KMS', 'ikon' => 'bi-journal-arrow-up',
                     'teks' => 'Hasil per kriteria didorong ke wiki Lebah instansi sebagai snapshot beriterasi.',
                     'deskripsi' => 'Halaman {slug}:Indikator_NXM_vN bersifat append-only per iterasi sehingga riwayat penilaian dapat ditelusuri; halaman dashboard pipeline dan audit diperbarui di tempat.',
                     'harga' => 'E2', 'hargaLama' => '', 'keterangan' => '/sakip_lyr1-publish-lebah',
                     'badge' => ['teks' => 'Layer 1', 'gaya' => 'new'], 'tag' => ['Redmine Lebah', 'per iterasi'], 'gambar' => ''],
                    ['kode' => 'l2', 'kategori' => 'Layer 2', 'judul' => 'Min Req gating', 'ikon' => 'bi-shield-check',
                     'teks' => '45 kriteria probe menentukan batas bawah predikat dan daftar gap.',
                     'deskripsi' => 'Gerbang Minimum Requirement hanya diterapkan pada predikat level gabungan; predikat Pusat atau Unit sendiri-sendiri bersifat indikatif. Keluaran: predikat final (AA sampai D) + daftar gap yang harus ditutup.',
                     'harga' => 'E2 → E3', 'hargaLama' => '', 'keterangan' => '/sakip_lyr2-minreq',
                     'badge' => ['teks' => 'Layer 2', 'gaya' => 'hot'], 'tag' => ['45 kriteria', 'predikat'], 'gambar' => ''],
                    ['kode' => 'lhe', 'kategori' => 'LHE', 'judul' => 'Draf LHE', 'ikon' => 'bi-file-earmark-text',
                     'teks' => 'Laporan Hasil Evaluasi 8 bagian, multi-gaya, disusun setelah Tinjauan Asesor.',
                     'deskripsi' => 'Draf LHE disusun dari skor, justifikasi, dan catatan asesor; nilai akhir instansi = rata-rata antar-LHE (LKE instansi + rata-rata LKE unit) ÷ 2.',
                     'harga' => 'E4 → E5', 'hargaLama' => '', 'keterangan' => '/sakip_lyr3-lhe-draft',
                     'badge' => ['teks' => 'LHE', 'gaya' => ''], 'tag' => ['8 bagian', 'multi-gaya'], 'gambar' => ''],
                    ['kode' => 'lhe', 'kategori' => 'LHE', 'judul' => 'Publikasi LHE', 'ikon' => 'bi-send-check',
                     'teks' => 'LHE final didorong ke halaman Laporan instansi di KMS Lebah.',
                     'deskripsi' => 'Halaman {slug}:Laporan menyimpan LHE final; deliverable per versi juga diarsipkan di penyimpanan gov3.',
                     'harga' => 'E5', 'hargaLama' => '', 'keterangan' => '/sakip_lyr3-lhe-publish',
                     'badge' => ['teks' => 'LHE', 'gaya' => ''], 'tag' => ['Laporan', 'arsip versi'], 'gambar' => ''],
                ],
                'tombol' => null,
            ],
            'promo'  => null,
            'galeri' => null,
            'sejarah' => [
                'kicker' => 'Perjalanan', 'judul' => 'Tonggak', 'judulAksen' => '2026',
                'teks'   => 'Dari instrumen sampai bukti kapasitas komputasi.',
                'daftar' => [
                    ['tahun' => 'Mei 2026',    'judul' => 'Pipeline 7 langkah hidup',
                     'teks' => 'Smoke test instansi piloting pertama; 20 instansi piloting mendapat wiki KMS Lebah; 640 portal instansi disiapkan di satu host.'],
                    ['tahun' => '27 Mei 2026', 'judul' => 'Prompt per kriteria pindah ke basis data',
                     'teks' => '1 906 baris prompt LKE dan 145 baris Min Req tersusun per bagian kanonik, dibaca MCP sakip-kriteria; tata kelola prompt 3 tingkat.'],
                    ['tahun' => '20 Jul 2026', 'judul' => 'LKE AKIP 2026 v3.1',
                     'teks' => 'Instrumen aktif: 65 kriteria Pusat + 54 Unit, bobot komponen 30/30/15/25 pada skala 100 di kedua level.'],
                    ['tahun' => '30 Jul 2026', 'judul' => 'Laporan PoC kapasitas komputasi',
                     'teks' => 'Pemrosesan dokumen 900–1 000 dokumen per jam pada GPU L40S; evaluasi LLM dengan model terbuka memerlukan minimal 4 GPU kelas 80 GB dalam satu domain NVLink.'],
                ],
            ],
            'tim' => [
                'kicker' => 'Pihak', 'judul' => 'Siapa di balik', 'judulAksen' => 'SAKIPAI',
                'daftar' => [
                    ['nama' => 'KemenPANRB',      'peran' => 'Klien & pemilik kebijakan',  'keterangan' => 'Evaluasi SAKIP, PermenPANRB 88/2021, LKE AKIP 2026', 'ikon' => 'bi-building',    'gambar' => '', 'sosmed' => []],
                    ['nama' => 'UI-CSGAR',        'peran' => 'Mitra riset tata kelola pemerintahan', 'keterangan' => 'Center for Study of Governance and Administrative Reform, Universitas Indonesia', 'ikon' => 'bi-mortarboard', 'gambar' => '', 'sosmed' => []],
                    ['nama' => 'Lab E-Government & E-Business', 'peran' => 'Sumber riset AI', 'keterangan' => 'Universitas Indonesia — bukan mitra langsung; penyedia referensi hasil riset penerapan AI dalam e-Government', 'ikon' => 'bi-lightbulb', 'gambar' => '',
                     'sosmed' => [['ikon' => 'bi-globe2', 'label' => 'egb.cs.ui.ac.id', 'url' => 'https://egb.cs.ui.ac.id/']]],
                ],
            ],
            'jam'       => null,
            'testimoni' => null,
            'reservasi' => [
                'kicker' => 'Piloting', 'judul' => 'Siapkan dokumen,', 'judulAksen' => 'mulai evaluasi',
                'teks'   => 'Induk Pemda dan tiap OPD dinilai sebagai scope independen; yang sudah lengkap boleh lanjut tanpa menunggu yang lain.',
                'infoJudul' => 'Yang disiapkan instansi', 'infoTeks' => 'Mengikuti Surat KemenPANRB B/103/AA.99/2025.',
                'info' => [
                    ['ikon' => 'bi-folder2-open',     'judul' => 'Dokumen wajib', 'teks' => '11 dokumen induk + 15 per OPD untuk 10 urusan piloting (hingga 161 dokumen)'],
                    ['ikon' => 'bi-file-earmark-pdf', 'judul' => 'Format',        'teks' => 'PDF teks (bukan pindaian), maksimal 5 MB per berkas'],
                    ['ikon' => 'bi-tag',              'judul' => 'Penamaan',      'teks' => '{induk|unit}_{slug}_{jenis}_{tahun}_{status}.pdf'],
                    ['ikon' => 'bi-speedometer2',     'judul' => 'Ambang audit',  'teks' => 'LENGKAP ≥ 90 % · CUKUP 70–89 % · KURANG 50–69 %'],
                ],
                'panel' => ['judul' => 'Portal instansi Anda sudah tersedia',
                            'teks'  => 'Masuk dengan akun portal, unggah dokumen wajib, dan pipeline berjalan per scope sampai Laporan Hasil Evaluasi.',
                            'tombol' => ['label' => 'Masuk ke portal', 'url' => 'login', 'ikon' => 'bi-box-arrow-in-right']],
            ],
            'berita' => null,
            'ajakan' => [
                'kicker' => 'Mulai', 'judul' => 'AI mengerjakan yang repetitif,', 'judulAksen' => 'asesor yang memutuskan',
                'teks'   => 'Setiap skor draf AI melewati Tinjauan Asesor sebelum menjadi LHE final.',
                'tombol' => ['label' => 'Masuk', 'url' => 'login', 'ikon' => 'bi-box-arrow-in-right'],
                'catatan' => 'Masuk memakai akun portal instansi (SSO gov3).',
            ],
            'hubungi' => [
                'kicker' => 'Kontak', 'judul' => 'Hubungi', 'judulAksen' => 'tim SAKIPAI',
                'teks'   => 'Untuk instansi piloting, mitra riset, dan pengembang.',
                'panelJudul' => 'Mari bicara', 'panelTeks' => 'Kami membalas pada jam kerja.',
                'peta' => '',
                'ajakJudul' => 'Kirim e-mail', 'ajakTeks' => 'Pertanyaan piloting, dokumen wajib, atau akses portal.',
                'tombol' => ['label' => 'Kirim e-mail', 'url' => 'mailto', 'ikon' => 'bi-send-fill'],
            ],
            'footer' => [
                'teks' => 'Tools analisis dokumen dan evaluasi SAKIP dengan pendekatan AI-in-the-Loop.',
                'kolom' => [
                    ['judul' => 'Halaman', 'tautan' => [
                        ['label' => 'Beranda', 'url' => '#hero'], ['label' => 'Tentang', 'url' => '#about'],
                        ['label' => 'Cara kerja', 'url' => '#menu'], ['label' => 'Pihak', 'url' => '#chefs'],
                        ['label' => 'Piloting', 'url' => '#reservation'], ['label' => 'Kontak', 'url' => '#contact-section'],
                    ]],
                    ['judul' => 'Rujukan', 'tautan' => [
                        ['label' => 'PermenPANRB 88/2021', 'url' => '#about'],
                        ['label' => 'LKE AKIP 2026 v3.1', 'url' => '#menu'],
                        ['label' => 'KMS Lebah (SAKIP)', 'url' => 'https://redmine.cybergl.co.id/projects/sakip'],
                        ['label' => 'Platform gov3', 'url' => '/'],
                    ]],
                ],
                'kontakJudul' => 'Hubungi kami',
                'bawah'   => [],
                'catatan' => 'Dibangun di atas platform gov3 (phpVB)',
                'kredit'  => ['nama' => 'Alif Zidane', 'url' => 'mailto:alif@cybergl.co.id'],
            ],
        ];
    }

	function dependencies () {
	}
}
