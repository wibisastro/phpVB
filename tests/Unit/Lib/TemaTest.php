<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\Tema;
use PHPUnit\Framework\TestCase;

/**
 * Tema tampilan per app (v5.1.10): apps/{app}/xml/tema.xml mengalahkan <template> config portal;
 * `cube` eksplisit di app = tetap cube walau portal memakai tema lain; nama tak valid / folder tak ada
 * jatuh ke tingkat berikutnya; tema general tidak pernah menjadi overlay.
 */
class TemaTest extends TestCase
{
    private string $akar;
    private string $template;

    protected function setUp(): void
    {
        $this->akar = sys_get_temp_dir() . '/phpvb-tema-' . bin2hex(random_bytes(4));
        $this->template = $this->akar . '/template';
        foreach (['cube', 'general', 'gelap', 'landing-page'] as $t) {
            mkdir($this->template . '/' . $t, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        $this->hapus($this->akar);
    }

    private function hapus(string $dir): void
    {
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $f) {
            $p = $dir . '/' . $f;
            is_dir($p) ? $this->hapus($p) : unlink($p);
        }
        rmdir($dir);
    }

    private function app(string $nama, ?string $tema): string
    {
        $dir = $this->akar . '/apps/' . $nama;
        mkdir($dir . '/xml', 0777, true);
        mkdir($dir . '/view', 0777, true);
        if ($tema !== null) {
            file_put_contents($dir . '/xml/tema.xml', "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tema>{$tema}</tema>\n");
        }
        return $dir;
    }

    public function testTanpaApaPunCube(): void
    {
        $p = Tema::pilih($this->template, '', $this->app('a', null));
        $this->assertSame('cube', $p['tema']);
        $this->assertSame('bawaan', $p['sumber']);
        $this->assertSame([$this->template . '/cube', $this->template . '/general'], $p['templates']);
    }

    public function testPortalMenjadiBawaanAppTanpaTemaSendiri(): void
    {
        $p = Tema::pilih($this->template, 'gelap', $this->app('a', null));
        $this->assertSame(['gelap', 'portal'], [$p['tema'], $p['sumber']]);
        $this->assertSame($this->template . '/gelap', $p['templates'][0]);
        // tanpa app sama sekali (handler inti) pun portal berlaku
        $this->assertSame('gelap', Tema::pilih($this->template, 'gelap', null)['tema']);
    }

    public function testAppMengalahkanPortal(): void
    {
        $p = Tema::pilih($this->template, '', $this->app('b', 'gelap'));
        $this->assertSame(['gelap', 'app'], [$p['tema'], $p['sumber']]);
        $this->assertSame([$this->template . '/gelap', $this->template . '/cube', $this->template . '/general'], $p['templates']);
        // dua app di satu portal, tema berbeda
        $this->assertSame('cube', Tema::pilih($this->template, '', $this->app('c', null))['tema']);
    }

    public function testCubeEksplisitDiAppMenolakTemaPortal(): void
    {
        $p = Tema::pilih($this->template, 'gelap', $this->app('d', 'cube'));
        $this->assertSame(['cube', 'app'], [$p['tema'], $p['sumber']]);
        $this->assertSame([$this->template . '/cube', $this->template . '/general'], $p['templates']);
    }

    public function testNamaTakValidAtauFolderTakAdaJatuhKeTingkatBerikutnya(): void
    {
        $this->assertSame('gelap', Tema::pilih($this->template, 'gelap', $this->app('e', 'tidak-ada'))['tema']);
        $this->assertSame('cube', Tema::pilih($this->template, 'tidak-ada', $this->app('f', '../cube'))['tema']);
        $this->assertSame('cube', Tema::pilih($this->template, 'general', $this->app('g', 'general'))['tema']);
        $this->assertNull(Tema::bacaApp($this->app('h', '')));
        $this->assertNull(Tema::bacaApp($this->akar . '/apps/tidak-ada'));
        $dir = $this->app('i', null);
        file_put_contents($dir . '/xml/tema.xml', '<tema>gelap');  // XML rusak
        $this->assertNull(Tema::bacaApp($dir));
        $this->assertSame('GELAP', strtoupper((string) Tema::bacaApp($this->app('j', ' Gelap '))), 'nama dirapikan ke huruf kecil');
    }

    public function testTemaHalamanPublikBolehJadiOverlayHanyaBilaDisebut(): void
    {
        // landing-page tanpa cube* tetap folder valid bagi pemilih; keputusan memakainya sebagai overlay ada di app
        $this->assertTrue(Tema::valid($this->template, 'landing-page'));
        $this->assertFalse(Tema::valid($this->template, 'cube'));
        $this->assertFalse(Tema::valid($this->template, 'general'));
        $this->assertFalse(Tema::valid($this->template, 'Gelap!'));
    }
}
