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
                "_SESSION['ctx_unit']",
            ] as $needle) {
                $this->assertStringNotContainsString($needle, $html, "template {$name} masih memuat chip konteks: {$needle}");
            }
        }
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
