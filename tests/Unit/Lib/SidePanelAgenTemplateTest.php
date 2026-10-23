<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use PHPUnit\Framework\TestCase;

/**
 * Kontrak template slot sidepanel `agen` (Agen Pesut/OWUI, dst.) di cube + gelap.
 *
 * Kontrak data `agenPanel` dan nama fungsi JS SAMA dengan sarab/agenPanel.html:
 * app memanggil window.openAgenPanel() di template mana pun. Keidentikan blok
 * sidepanel cube vs gelap ditegakkan SidePanelKancilTemplateTest; di sini kait
 * per elemen + aturan login-tidak-di-iframe (gov2:Pola_Login_Popup_Iframe).
 */
class SidePanelAgenTemplateTest extends TestCase
{
    private const LAYOUTS = [
        'cube' => __DIR__ . '/../../../core/template/cube/cubeLayout.html',
        'gelap' => __DIR__ . '/../../../core/template/gelap/cubeLayout.html',
    ];
    private const HEADERS = [
        'cube' => __DIR__ . '/../../../core/template/cube/cubeHeader.html',
        'gelap' => __DIR__ . '/../../../core/template/gelap/cubeHeader.html',
    ];

    /** @param array<string,string> $files @return array<string,string> */
    private function bodies(array $files): array
    {
        $out = [];
        foreach ($files as $name => $path) {
            $this->assertFileExists($path);
            $out[$name] = (string) file_get_contents($path);
        }
        return $out;
    }

    public function testHookSlotAgenTersediaDiKeduaLayout(): void
    {
        foreach ($this->bodies(self::LAYOUTS) as $name => $html) {
            foreach ([
                'id="sidePanel-agen"',
                'id="sidePanelAgenFrame"',
                'id="sidePanelAgenBar"',
                'window.openAgenPanel',
                'window.agenPanelMuatUlang',
                'data-src="{{ agenPanel.url }}"',
                "'wilayah', 'instansi', 'pengaturan', 'filter', 'chat', 'kancil', 'agen'",
            ] as $needle) {
                $this->assertStringContainsString($needle, $html, "layout {$name} kehilangan {$needle}");
            }
        }
    }

    public function testSlotMatiTanpaUrlDanDigerbangWajibLogin(): void
    {
        // Default 633 portal: agenPanel kosong → slot tidak dirender. wajibLogin → butuh sesi portal.
        foreach ($this->bodies(self::LAYOUTS) as $name => $html) {
            $this->assertMatchesRegularExpression(
                '/\{% if agenPanel\.url\|default\(\x27\x27\) and \(not agenPanel\.wajibLogin\|default\(false\) or _SESSION\[\x27account_id\x27\]\|default\(false\)\) %\}\s*\{#.*?#\}\s*<div id="sidePanel-agen"/s',
                $html,
                "layout {$name}: slot agen harus digerbang agenPanel.url + wajibLogin/_SESSION"
            );
        }
    }

    public function testLoginLewatPopupBukanDiDalamIframe(): void
    {
        foreach ($this->bodies(self::LAYOUTS) as $name => $html) {
            $this->assertDoesNotMatchRegularExpression(
                '/<iframe id="sidePanelAgenFrame"[^>]*\ssrc="/',
                $html,
                "layout {$name}: iframe agen tidak boleh punya src statis (lazy saat panel dibuka)"
            );
            $this->assertMatchesRegularExpression(
                '/id="sidePanelAgenMasuk"[^>]*target="_blank"/',
                $html,
                "layout {$name}: tombol masuk agen harus target=_blank"
            );
            $this->assertStringContainsString("window.open(masuk.href, 'agen-login'", $html, "layout {$name}: login agen harus lewat popup");
            $this->assertMatchesRegularExpression(
                '/if \(view === \x27agen\x27\) \{ agenEnsureLoaded\(\); \}/',
                $html,
                "layout {$name}: openSidePanel(\x27agen\x27) harus mengisi iframe"
            );
            $this->assertStringContainsString("view === 'kancil' || view === 'agen'", $html, "layout {$name}: slot agen harus memakai lebar sidepanel-wide");
        }
    }

    public function testItemDropdownProfilDiKeduaHeader(): void
    {
        // Pemicu = item di dropdown profil, di sebelah item Kancil (keputusan Wibi 23 Okt 2026);
        // blok dropdown itu hanya untuk yang sudah login.
        foreach ($this->bodies(self::HEADERS) as $name => $html) {
            $this->assertMatchesRegularExpression(
                '/\{% if agenPanel\.url\|default\(\x27\x27\) %\}.*?openAgenPanel\(\).*?\{% endif %\}/s',
                $html,
                "header {$name}: item dropdown agen harus digerbang agenPanel.url"
            );
            $posKancil = strpos($html, "openSidePanel('kancil')");
            $posAgen = strpos($html, 'openAgenPanel()');
            $posLogout = strpos($html, '/logout');
            $this->assertIsInt($posKancil);
            $this->assertIsInt($posAgen);
            $this->assertTrue($posKancil < $posAgen && $posAgen < $posLogout, "header {$name}: item agen harus di antara Kancil dan Logout");
        }
    }
}
