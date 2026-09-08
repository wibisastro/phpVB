<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use PHPUnit\Framework\TestCase;

/**
 * Kontrak template untuk indikator konteks konten di topbar.
 *
 * Rujukan: rencana/filter-konteks-instansi-wilayah.md §3.2 (key sesi ctx_*) dan
 * fase 2 ("cubeHeader.html (cube + gelap): indikator konteks gabungan").
 *
 * Dua hal yang dijaga di sini:
 *  1. NO-OP — chip hanya dirender bila _SESSION['ctx_slug'] terisi DAN akun login,
 *     sehingga 632 portal non-supervisor melihat topbar yang persis sama.
 *  2. Paritas cube ≡ gelap — riwayat repo ini menunjukkan kelas kesalahan
 *     "cube disunting, gelap terlupa" (lihat SidePanelKancilTemplateTest).
 */
class HeaderKonteksTemplateTest extends TestCase
{
    private const TEMPLATES = [
        'cube' => __DIR__ . '/../../../core/template/cube/cubeHeader.html',
        'gelap' => __DIR__ . '/../../../core/template/gelap/cubeHeader.html',
    ];

    private const MARK_START = '<!-- Indikator konteks konten start -->';
    private const MARK_END = '<!-- Indikator konteks konten ends -->';

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

    /** @return array<string,string> potongan blok chip konteks per skin */
    private function blocks(): array
    {
        $out = [];
        foreach ($this->bodies() as $name => $html) {
            $start = strpos($html, self::MARK_START);
            $end = strpos($html, self::MARK_END);
            $this->assertIsInt($start, "penanda awal chip konteks hilang di {$name}");
            $this->assertIsInt($end, "penanda akhir chip konteks hilang di {$name}");
            $out[$name] = substr($html, $start, $end - $start);
        }

        return $out;
    }

    public function testChipKonteksAdaDiKeduaTemplate(): void
    {
        foreach ($this->blocks() as $name => $block) {
            foreach ([
                "{% if _SESSION['ctx_slug'] %}",
                'id="topbarKonteks"',
                'bi bi-layers',
                "openSidePanel('instansi')",
                'Konten yang ditampilkan berasal dari instansi ini',
                "Konteks: {{ _SESSION['ctx_slug'] }}",
                "{% if _SESSION['ctx_unit'] %} / {{ _SESSION['ctx_unit'] }}{% endif %}",
            ] as $needle) {
                $this->assertStringContainsString($needle, $block, "template {$name} kehilangan {$needle}");
            }
        }
    }

    public function testChipBersarangDiDalamBlokAkunLogin(): void
    {
        // Chip tidak boleh bocor ke pengunjung anonim: harus di dalam
        // {% if _SESSION['account_id'] %} milik header action bar.
        foreach ($this->bodies() as $name => $html) {
            $bar = strpos($html, '<!-- Header action bar starts -->');
            $this->assertIsInt($bar, "penanda header action bar hilang di {$name}");

            $if = strpos($html, "{% if _SESSION['account_id'] %}", $bar);
            $chip = strpos($html, self::MARK_START, $bar);
            $this->assertIsInt($if, "guard account_id hilang di {$name}");
            $this->assertIsInt($chip, "chip konteks tidak berada di header action bar {$name}");
            $this->assertGreaterThan($if, $chip, "chip konteks mendahului guard account_id di {$name}");

            // Guard belum ditutup saat chip muncul: hitung kedalaman Twig di
            // antaranya (blok wilayah/instansi punya {% if %} bersarang sendiri).
            $antara = substr($html, $if, $chip - $if);
            $buka = preg_match_all('/\{%\s*if\s/', $antara);
            $tutup = preg_match_all('/\{%\s*endif\s*%\}/', $antara);
            $this->assertGreaterThan(
                $tutup,
                $buka,
                "template {$name}: guard account_id sudah ditutup sebelum chip konteks"
            );
        }
    }

    public function testStrukturChipIdentikAntaraCubeDanGelap(): void
    {
        // Warna boleh berbeda (skin gelap pakai text-warning-emphasis), sisanya
        // — markup, Twig, teks, id — harus sama persis.
        $norm = static fn (string $s): string => (string) preg_replace(
            ['/class="[^"]*"/', '/style="[^"]*"/'],
            ['class="*"', 'style="*"'],
            $s
        );

        $blocks = $this->blocks();
        $this->assertSame(
            $norm($blocks['cube']),
            $norm($blocks['gelap']),
            'chip konteks cube dan gelap sudah menyimpang — samakan keduanya'
        );
    }

    public function testIdYangDipakaiJsTidakBerubah(): void
    {
        foreach ($this->bodies() as $name => $html) {
            foreach (['id="topbarWilayah"', 'id="topbarWilayahMenu"', 'id="topbarUnitKerja"'] as $needle) {
                $this->assertStringContainsString($needle, $html, "template {$name} kehilangan {$needle}");
            }
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
