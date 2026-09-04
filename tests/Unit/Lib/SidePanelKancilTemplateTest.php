<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use PHPUnit\Framework\TestCase;

/**
 * Kontrak template untuk slot sidepanel `kancil`.
 *
 * App consumer (mis. SAKIP ingest/aisakip) memanggil window.openKancilPanel()
 * dan bergantung pada id elemen di bawah. Dua template — cube dan gelap —
 * harus tetap seragam: riwayat repo ini menunjukkan kelas kesalahan "cube
 * disunting, gelap terlupa" (lihat commit cache-bust 92e4d2c/e2edd4b).
 */
class SidePanelKancilTemplateTest extends TestCase
{
    private const TEMPLATES = [
        'cube' => __DIR__ . '/../../../core/template/cube/cubeLayout.html',
        'gelap' => __DIR__ . '/../../../core/template/gelap/cubeLayout.html',
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

    public function testHookYangDipakaiAppTersediaDiKeduaTemplate(): void
    {
        foreach ($this->bodies() as $name => $html) {
            foreach ([
                'window.openKancilPanel',
                'id="sidePanel-kancil"',
                'id="sidePanelKancilFrame"',
                'id="sidePanelKancilContext"',
                'id="sidePanelKancilContextLabel"',
                'id="sidePanelKancilPrompts"',
                'data-kancil-prompt',
            ] as $needle) {
                $this->assertStringContainsString($needle, $html, "template {$name} kehilangan {$needle}");
            }
        }
    }

    public function testBlokSidePanelIdentikAntaraCubeDanGelap(): void
    {
        $blocks = [];
        foreach ($this->bodies() as $name => $html) {
            $start = strpos($html, '<!-- Side Panel Offcanvas starts -->');
            $end = strpos($html, '<!-- Cube sidebar custom JS -->');
            $this->assertIsInt($start, "penanda awal sidepanel hilang di {$name}");
            $this->assertIsInt($end, "penanda akhir sidepanel hilang di {$name}");
            $blocks[$name] = substr($html, $start, $end - $start);
        }
        $this->assertSame(
            $blocks['cube'],
            $blocks['gelap'],
            'blok sidepanel cube dan gelap sudah menyimpang — samakan keduanya'
        );
    }

    public function testKonteksDibersihkanSaatPanelDibukaPolos(): void
    {
        foreach ($this->bodies() as $name => $html) {
            $this->assertMatchesRegularExpression(
                '/if \(view === \x27kancil\x27\).*sidePanelKancilContext/s',
                $html,
                "template {$name}: openSidePanel(\x27kancil\x27) tidak lagi mereset strip konteks"
            );
        }
    }
}
