<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use PHPUnit\Framework\TestCase;

/**
 * Kontrak template topbar untuk fitur konteks konten.
 *
 * Riwayat: fase 2 (phpVB 5f9c283) menambahkan chip "Konteks: {slug}" di
 * cubeHeader cube + gelap. Keputusan Wibi 11 Sep 2026: chip DIBUANG — label
 * Instansi/Wilayah di topbar sudah menampilkan nama pilihan, chip hanya
 * mengulang slug mentah dan (karena dirender Twig) basi sampai halaman dimuat
 * ulang. Tes ini menjaga agar chip tidak kembali diam-diam dan agar id yang
 * dipakai JS panel tetap ada di kedua skin.
 */
class HeaderKonteksTemplateTest extends TestCase
{
    private const TEMPLATES = [
        'cube' => __DIR__ . '/../../../core/template/cube/cubeHeader.html',
        'gelap' => __DIR__ . '/../../../core/template/gelap/cubeHeader.html',
    ];

    /** @return array<string,string> */
    private function bodies(): array
    {
        $out = [];
        foreach (self::TEMPLATES as $name => $path) {
            $this->assertFileExists($path);
            $out[$name] = (string) file_get_contents($path);
        }

        return $out;
    }

    public function testChipKonteksSudahDibuangDiKeduaTemplate(): void
    {
        foreach ($this->bodies() as $name => $html) {
            foreach ([
                'id="topbarKonteks"',
                'Indikator konteks konten',
                "Konteks: {{ _SESSION['ctx_slug'] }}",
            ] as $needle) {
                $this->assertStringNotContainsString($needle, $html, "template {$name} masih memuat chip konteks: {$needle}");
            }
        }
    }

    /** @return array<string,string> blok indikator Instansi per skin */
    private function blokInstansi(): array
    {
        $out = [];
        foreach ($this->bodies() as $name => $html) {
            $start = strpos($html, '<!-- Instansi indicator start -->');
            $end = strpos($html, '<!-- Instansi indicator ends -->');
            $this->assertIsInt($start, "penanda awal indikator instansi hilang di {$name}");
            $this->assertIsInt($end, "penanda akhir indikator instansi hilang di {$name}");
            $out[$name] = substr($html, $start, $end - $start);
        }

        return $out;
    }

    public function testLabelIndukMenempelPadaIndikatorInstansi(): void
    {
        // Pengganti chip (Wibi 11 Sep): unit terpilih → "Inspektorat · Kementerian PANRB".
        // Hanya bila ctx_unit DAN ctx_nama terisi (mode supervisor); portal biasa tak berubah.
        foreach ($this->blokInstansi() as $name => $blok) {
            $this->assertStringContainsString("{% if _SESSION['ctx_unit'] and _SESSION['ctx_nama'] %}", $blok, "label induk hilang di {$name}");
            $this->assertStringContainsString("{{ _SESSION['ctx_nama'] }}", $blok, "nama induk tidak dirender di {$name}");
            $this->assertStringContainsString("{{ _SESSION['unit_nama'] }}", $blok, "nama unit hilang di {$name}");
        }
    }

    public function testStrukturIndikatorInstansiIdentikAntaraCubeDanGelap(): void
    {
        $norm = static fn (string $s): string => (string) preg_replace(
            ['/class="[^"]*"/', '/style="[^"]*"/'],
            ['class="*"', 'style="*"'],
            $s
        );
        $blok = $this->blokInstansi();
        $this->assertSame($norm($blok['cube']), $norm($blok['gelap']), 'indikator instansi cube dan gelap menyimpang — samakan keduanya');
    }

    public function testIdYangDipakaiJsTidakBerubah(): void
    {
        foreach ($this->bodies() as $name => $html) {
            foreach (['id="topbarWilayah"', 'id="topbarWilayahMenu"', 'id="topbarUnitKerja"'] as $needle) {
                $this->assertStringContainsString($needle, $html, "template {$name} kehilangan {$needle}");
            }
        }
    }

    public function testIndikatorInstansiMasihDiDalamGuardAkunLogin(): void
    {
        // Indikator Instansi/Wilayah tetap di dalam {% if _SESSION['account_id'] %}
        // milik header action bar — pembuangan chip tidak boleh merusak guard itu.
        foreach ($this->bodies() as $name => $html) {
            $bar = strpos($html, '<!-- Header action bar starts -->');
            $this->assertIsInt($bar, "penanda header action bar hilang di {$name}");
            $if = strpos($html, "{% if _SESSION['account_id'] %}", $bar);
            $unit = strpos($html, 'id="topbarUnitKerja"', $bar);
            $this->assertIsInt($if, "guard account_id hilang di {$name}");
            $this->assertIsInt($unit, "indikator instansi hilang dari header action bar {$name}");
            $this->assertGreaterThan($if, $unit, "indikator instansi mendahului guard account_id di {$name}");
        }
    }

    public function testAkhirBarisKeduaTemplateSeragam(): void
    {
        // Kedua file historisnya CRLF; penyuntingan tidak boleh menormalkan
        // salah satunya saja (diff jadi seluruh file dan paritas sulit dibaca).
        $bodies = $this->bodies();
        $this->assertSame(
            str_contains($bodies['cube'], "\r\n"),
            str_contains($bodies['gelap'], "\r\n"),
            'akhir baris cubeHeader cube dan gelap tidak lagi seragam (LF vs CRLF)'
        );
    }
}
