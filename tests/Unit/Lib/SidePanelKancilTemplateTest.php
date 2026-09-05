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
                'data-kancil-copy',
                'window.kancilAsk',
                'window.kancilReloadPanel',
                'id="sidePanelKancilLogin"',
                'id="sidePanelKancilStatus"',
                'data-kancil-dir="{{ kancilDir }}"',
                'data-kancil-b64="{{ kancilB64 }}"',
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

    public function testLoginTidakPernahDimuatDiDalamIframe(): void
    {
        // #10723 brief 2026-09-05 + #10725: tombol masuk membuka tab baru; iframe
        // mulai tanpa src dan tersembunyi sampai kancilProbe() memastikan ada sesi.
        foreach ($this->bodies() as $name => $html) {
            $this->assertMatchesRegularExpression(
                '/id="sidePanelKancilLoginBtn"[^>]*target="_blank"/',
                $html,
                "template {$name}: tombol Masuk ke Kancil harus target=_blank"
            );
            $this->assertMatchesRegularExpression(
                '/<iframe id="sidePanelKancilFrame" data-src="[^"]*"(?:(?!<\/iframe>).)*display:none/s',
                $html,
                "template {$name}: iframe kancil harus mulai tersembunyi tanpa src"
            );
            $this->assertDoesNotMatchRegularExpression(
                '/<iframe id="sidePanelKancilFrame"[^>]*\ssrc="/',
                $html,
                "template {$name}: iframe kancil tidak boleh punya src statis"
            );
            $this->assertMatchesRegularExpression(
                '/if \(view === \x27kancil\x27\).*kancilEnsureSession\(\)/s',
                $html,
                "template {$name}: openSidePanel(\x27kancil\x27) harus lewat kancilEnsureSession()"
            );
        }
    }

    public function testKancilAskMengikutiKontrakTigaLangkah(): void
    {
        foreach ($this->bodies() as $name => $html) {
            $pos1 = strpos($html, "'/session' + q");
            $pos2 = strpos($html, "'/message' + q");
            $pos3 = strpos($html, "'/session/' + encodeURIComponent(s.id)");
            $this->assertNotFalse($pos1, "template {$name}: POST /session hilang");
            $this->assertNotFalse($pos2, "template {$name}: POST /session/{id}/message hilang");
            $this->assertNotFalse($pos3, "template {$name}: navigasi iframe ke /<b64>/session/<id> hilang");
            $this->assertStringContainsString("credentials: 'include'", $html, "template {$name}: fetch tanpa cookie kancil");
            $this->assertStringContainsString("redirect: 'manual'", $html, "template {$name}: 302 oauth2-proxy harus terdeteksi sebagai BELUM_LOGIN");
        }
    }
}
