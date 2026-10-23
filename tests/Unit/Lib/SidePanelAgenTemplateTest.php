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
                'id="sidePanelAgenReload"',
                '#sidePanelOffcanvas.sidepanel-agen { width: min(50vw, 960px) !important;',
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

    public function testIframeLazyTanpaTombolMasukDanSelebarSarab(): void
    {
        // Keputusan Wibi 23 Okt 2026: tidak ada tombol "Masuk" di panel agen cube (pengguna portal
        // sudah ber-SSO beo); muat ulang lewat tombol di kepala offcanvas; lebar = panel agen sarab.
        $sarab = (string) file_get_contents(__DIR__ . '/../../../core/template/sarab/agenPanel.html');
        $this->assertMatchesRegularExpression('/#agenPanelOffcanvas \{ width: min\(50vw, 960px\); min-width: 480px; \}/', $sarab);
        foreach ($this->bodies(self::LAYOUTS) as $name => $html) {
            $this->assertDoesNotMatchRegularExpression(
                '/<iframe id="sidePanelAgenFrame"[^>]*\ssrc="/',
                $html,
                "layout {$name}: iframe agen tidak boleh punya src statis (lazy saat panel dibuka)"
            );
            $this->assertStringNotContainsString('sidePanelAgenMasuk', $html, "layout {$name}: panel agen cube tidak boleh punya tombol masuk");
            $this->assertStringNotContainsString('agenPanel.loginUrl', $html, "layout {$name}: loginUrl tidak dipakai di cube");
            $this->assertMatchesRegularExpression(
                '/if \(view === \x27agen\x27\) \{ agenEnsureLoaded\(\); \}/',
                $html,
                "layout {$name}: openSidePanel(\x27agen\x27) harus mengisi iframe"
            );
            $this->assertStringContainsString("panel.classList.toggle('sidepanel-agen', view === 'agen')", $html, "layout {$name}: lebar khusus agen tidak di-toggle");
            $this->assertStringContainsString("panel.classList.toggle('sidepanel-wide', view === 'kancil')", $html, "layout {$name}: lebar kancil berubah");
            $this->assertMatchesRegularExpression(
                '/#sidePanelOffcanvas\.sidepanel-agen \{ width: min\(50vw, 960px\) !important; min-width: 480px !important; \}/',
                $html,
                "layout {$name}: lebar slot agen harus sama dengan sarab (min(50vw, 960px), min 480px)"
            );
            $this->assertMatchesRegularExpression(
                '/id="sidePanelAgenReload"[^>]*onclick="agenPanelMuatUlang\(\)"/',
                $html,
                "layout {$name}: tombol muat ulang agen di kepala offcanvas hilang"
            );
            $this->assertStringContainsString("reload.style.display = (view === 'agen') ? '' : 'none'", $html, "layout {$name}: tombol muat ulang harus hanya tampil di view agen");
        }
    }

    public function testCssSidepanelDiLuarAkarVueDanSlotTersembunyiBenar(): void
    {
        // Regresi 23 Okt 2026 (#11099): (1) <style> di dalam <div id="app"> dibuang Vue 3 saat mount,
        // lebar slot tidak pernah berlaku; (2) d-flex (display:flex !important) mengalahkan
        // style display:none, slot agen ikut tampil di view Pengaturan; (3) ↻ jatuh di tengah header.
        foreach ($this->bodies(self::LAYOUTS) as $name => $html) {
            $posAppEnd = strpos($html, '<!-- Page wrapper ends -->');
            $posCss = strpos($html, '#sidePanelOffcanvas.sidepanel-agen {');
            $this->assertIsInt($posAppEnd);
            $this->assertIsInt($posCss);
            $this->assertGreaterThan($posAppEnd, $posCss, "layout {$name}: CSS sidepanel harus di luar <div id=\"app\">");
            $this->assertGreaterThan($posAppEnd, (int) strpos($html, '#sidePanelOffcanvas.sidepanel-wide {'), "layout {$name}: CSS kancil harus di luar #app");
            foreach (['sidePanel-agen', 'sidePanel-kancil'] as $id) {
                $this->assertMatchesRegularExpression(
                    '/<div id="' . $id . '" class="[^"]*\bd-none\b[^"]*"/',
                    $html,
                    "layout {$name}: {$id} ber-d-flex harus mulai dengan d-none"
                );
            }
            $this->assertStringContainsString("el.classList.toggle('d-none', v !== view);", $html, "layout {$name}: openSidePanel harus men-toggle d-none");
            $this->assertMatchesRegularExpression(
                '/<div class="ms-auto d-flex align-items-center gap-2">\s*<button type="button" id="sidePanelAgenReload"[^>]*>.*?<\/button>\s*<button type="button" class="btn-close"/s',
                $html,
                "layout {$name}: ↻ dan tombol tutup harus satu wadah ms-auto (mepet kanan)"
            );
            $this->assertDoesNotMatchRegularExpression('/id="sidePanelAgenReload" class="[^"]*ms-auto/', $html, "layout {$name}: ↻ tidak boleh ms-auto sendiri");
        }
    }

    public function testPromptDariHalamanLewatDeepLinkDiCubeGelapDanSarab(): void
    {
        // Skenario Wibi 23 Okt 2026: ikon chat di halaman (mis. aisakip/evaluasi kriteria) mengirim
        // prompt ke Pesut di panel yang masih tertutup → panel terbuka, prompt sudah terkirim.
        // Jalur = deep link OWUI ?models=…&q=…[&submit=false] lewat src iframe.
        $files = self::LAYOUTS + ['sarab' => __DIR__ . '/../../../core/template/sarab/agenPanel.html'];
        foreach ($this->bodies($files) as $name => $html) {
            foreach ([
                "function agenPromptUrl(base, opts)",
                "u.searchParams.set('models', model)",
                "u.searchParams.set('q', String(opts.prompt).slice(0, 4000))",
                "if (opts.kirim === false) { u.searchParams.set('submit', 'false'); }",
                "closest('[data-agen-prompt]')",
                "el.getAttribute('data-agen-model')",
                "el.getAttribute('data-agen-kirim') !== 'false'",
                'data-model="{{ agenPanel.model|default(\'\') }}"',
                'window.agenPanelTersedia',
            ] as $needle) {
                $this->assertStringContainsString($needle, $html, "{$name} kehilangan {$needle}");
            }
            $this->assertMatchesRegularExpression(
                '/window\.openAgenPanel = function ?\(opts\)/',
                $html,
                "{$name}: openAgenPanel harus menerima opts {prompt, model, kirim}"
            );
            $this->assertStringContainsString('model: opts.model || ', $html, "{$name}: model bawaan dari agenPanel.model tidak dipakai");
        }
        $sarab = (string) file_get_contents($files['sarab']);
        $this->assertStringContainsString("if (oc.dataset.terkunci === '1') { return false; }", $sarab, 'sarab: prompt tidak boleh membuka panel yang terkunci wajibLogin');
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
