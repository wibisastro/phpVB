<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\document;
use PHPUnit\Framework\TestCase;

/**
 * document::agenPanelConfig() — default `agenPanel` (slot sidepanel `agen`) dari
 * config instance <platform><agen …/>. Kontrak = sarab/agenPanel.html.
 */
class DocumentAgenPanelTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['config'] = new \SimpleXMLElement('<config/>');
    }

    private function doc(): document
    {
        return new document();
    }

    public function testKosongTanpaConfig(): void
    {
        $this->assertSame([], $this->doc()->agenPanelConfig());
        $GLOBALS['config'] = new \SimpleXMLElement('<config><platform/></config>');
        $this->assertSame([], $this->doc()->agenPanelConfig());
    }

    public function testKosongBilaUrlTidakValid(): void
    {
        foreach (['', 'pesut.sakip.ai.id', 'javascript:alert(1)', 'ftp://x.y/', 'https://a b/'] as $url) {
            $GLOBALS['config'] = new \SimpleXMLElement(
                '<config><platform><agen nama="Agen Pesut" url="' . htmlspecialchars($url) . '"/></platform></config>'
            );
            $this->assertSame([], $this->doc()->agenPanelConfig(), "url '{$url}' harus mematikan slot");
        }
    }

    public function testLengkapDariAtribut(): void
    {
        $GLOBALS['config'] = new \SimpleXMLElement(
            '<config><platform><agen nama="Agen Pesut" url="https://pesut.sakip.ai.id/"'
            . ' login="https://pesut.sakip.ai.id/oauth/oidc/login" loginLabel="Masuk dengan beo"'
            . ' tombol="Tanya Pesut" ikon="bi-robot" wajibLogin="1"/></platform></config>'
        );
        $this->assertSame([
            'nama' => 'Agen Pesut',
            'url' => 'https://pesut.sakip.ai.id/',
            'loginUrl' => 'https://pesut.sakip.ai.id/oauth/oidc/login',
            'loginLabel' => 'Masuk dengan beo',
            'tombol' => 'Tanya Pesut',
            'ikon' => 'bi-robot',
            'wajibLogin' => true,
            'model' => '',
        ], $this->doc()->agenPanelConfig());
    }

    public function testDefaultMinimalDanWajibLoginBawaanTrue(): void
    {
        // Hanya url (sebagai isi elemen) — sisanya bawaan; wajibLogin TRUE bila tidak disebut
        // (tombol agen hanya untuk yang sudah login portal).
        $GLOBALS['config'] = new \SimpleXMLElement(
            '<config><platform><agen>https://pesut.sakip.ai.id/</agen></platform></config>'
        );
        $this->assertSame([
            'nama' => 'Agen',
            'url' => 'https://pesut.sakip.ai.id/',
            'loginUrl' => '',
            'loginLabel' => 'Masuk',
            'tombol' => 'Tanya Agen',
            'ikon' => 'bi-robot',
            'wajibLogin' => true,
            'model' => '',
        ], $this->doc()->agenPanelConfig());
    }

    public function testModelBawaanUntukPromptDanDisaring(): void
    {
        $GLOBALS['config'] = new \SimpleXMLElement(
            '<config><platform><agen url="https://pesut.sakip.ai.id/" model="asesor-sakip-ai"/></platform></config>'
        );
        $this->assertSame('asesor-sakip-ai', $this->doc()->agenPanelConfig()['model']);
        $GLOBALS['config'] = new \SimpleXMLElement(
            '<config><platform><agen url="https://pesut.sakip.ai.id/" model="x&quot; onload=&quot;y"/></platform></config>'
        );
        $this->assertSame('', $this->doc()->agenPanelConfig()['model'], 'model dengan karakter aneh dibuang');
    }

    public function testWajibLoginBisaDimatikanDanNilaiAnehDisaring(): void
    {
        $GLOBALS['config'] = new \SimpleXMLElement(
            '<config><platform><agen nama="Pesut" url="https://pesut.sakip.ai.id/" login="bukan-url"'
            . ' ikon="bi-robot&quot; onload=&quot;x" wajibLogin="false"/></platform></config>'
        );
        $a = $this->doc()->agenPanelConfig();
        $this->assertFalse($a['wajibLogin']);
        $this->assertSame('', $a['loginUrl'], 'login yang bukan URL http(s) dibuang');
        $this->assertSame('bi-robot', $a['ikon'], 'ikon dengan karakter di luar kelas CSS jatuh ke bawaan');
        $this->assertSame('Tanya Pesut', $a['tombol']);
    }
}
