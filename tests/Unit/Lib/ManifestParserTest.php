<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\Manifest\Parser;
use PHPUnit\Framework\TestCase;

/**
 * Pengurai manifest.md — fixture tests/fixture/manifest/bukutamu.md dipakai bersama generator
 * gov3phpvb (manifest.py) dan MCP phpvb-run supaya tiga pihak membaca bentuk yang sama.
 */
class ManifestParserTest extends TestCase
{
    private static function fixture(): string
    {
        return (string) file_get_contents(__DIR__ . '/../../fixture/manifest/bukutamu.md');
    }

    public function testFixtureTerurai(): void
    {
        $u = Parser::urai(self::fixture());

        self::assertSame([], $u['galat']);
        self::assertSame('Manifest API — bukutamu v1.2.0', $u['judul']);
        self::assertSame('1.2.0', $u['versi']);
        self::assertSame('bukutamu', $u['app']['app']);
        self::assertSame(1, $u['app']['skema']);
        self::assertSame(['jenis_keperluan', 'kunjungan'], array_keys($u['mvc']));

        $k = $u['mvc']['kunjungan']['data'];
        self::assertSame(['P1', 'M4', 'M5'], $k['pola']);
        self::assertSame('POST', $k['cmd']['add']['method']);
        self::assertSame(['nama', 'tanggal'], $k['cmd']['add']['wajib']);
        self::assertSame('edit', $k['cmd']['del']['konfirmasi']);
        self::assertSame(['table', 'edit', 'del'], $k['alur']['hapus']);
        self::assertStringStartsWith('### kunjungan', $u['mvc']['kunjungan']['md']);
        self::assertStringNotContainsString('manifest:mvc', $u['mvc']['kunjungan']['md']);
    }

    public function testRingkasUntukIndeks(): void
    {
        $r = Parser::ringkas(Parser::urai(self::fixture()));

        self::assertSame('Kunjungan Tamu', $r['kunjungan']['judul']);
        self::assertContains('del', $r['kunjungan']['cmd']);
        self::assertSame(['tambah', 'ubah', 'hapus', 'cari'], $r['kunjungan']['alur']);
        self::assertSame(['level' => 'admin', 'scope' => 'ctor'], $r['kunjungan']['auth']);
        self::assertFalse($r['kunjungan']['perlu_dilengkapi']);
    }

    public function testTanpaBlokDataMenghilangkanFenceTapiBukanTabel(): void
    {
        $md = Parser::tanpaBlokData(self::fixture());

        self::assertStringNotContainsString('```json manifest', $md);
        self::assertStringNotContainsString('"skema": 1', $md);
        self::assertStringContainsString('| del | POST |', $md);
        self::assertStringContainsString('### kunjungan — Kunjungan Tamu', $md);
    }

    public function testGalatDilaporkanBernamaBukanDilempar(): void
    {
        $md = "# Manifest API — x v0.1.0\n"
            . "<!-- manifest:mvc:satu start -->\n### satu\n<!-- manifest:mvc:satu end -->\n"
            . "<!-- manifest:mvc:dua start -->\n```json manifest\n{\"mvc\":\"lain\",\"cmd\":{\"add\":{\"method\":\"PUT\"}}}\n```\n<!-- manifest:mvc:dua end -->\n"
            . "<!-- manifest:mvc:tiga start -->\n```json manifest\n{bukan json}\n```\n<!-- manifest:mvc:tiga end -->\n"
            . "<!-- manifest:mvc:empat start -->\n### tanpa penutup\n";
        $u = Parser::urai($md);

        self::assertSame('0.1.0', $u['versi'], 'versi jatuh ke judul bila blok app tidak ada');
        self::assertSame(['satu', 'dua', 'tiga'], array_keys($u['mvc']));
        self::assertNull($u['mvc']['satu']['data']);
        self::assertContains('MVC satu: blok data ```json manifest``` tidak ada', $u['galat']);
        self::assertContains("MVC dua: blok data menyebut mvc 'lain'", $u['galat']);
        self::assertContains('MVC dua: cmd add method harus GET/POST', $u['galat']);
        self::assertContains('MVC dua: cmd add tanpa uri', $u['galat']);
        self::assertNotEmpty(array_filter($u['galat'], fn($g) => str_starts_with($g, 'MVC tiga: JSON tidak valid')));
        self::assertContains('MVC empat: tanda end tidak ditemukan', $u['galat']);
    }

    public function testVersiJatuhKeReleaseBilaManifestTanpaVersi(): void
    {
        $md = "# Manifest API — x\n";
        self::assertSame('', Parser::versi($md));
        self::assertSame('2.0.1', Parser::versi($md, [], '', "# Riwayat\n\n## v2.0.1 — 2026-10-08\n- awal\n\n## v2.0.0 — 2026-10-01\n"));
        self::assertSame('3.1.4-beta.2', Parser::versiRelease("## 3.1.4-beta.2 (2026-10-09)\n"));
        self::assertSame('9.9.9', Parser::versi($md, ['versi' => '9.9.9'], '', "## v1.0.0\n"), 'blok app menang');
    }

    public function testPisahJudul(): void
    {
        self::assertSame(['Judul A', "isi\n"], Parser::pisahJudul("# Judul A\n\nisi\n"));
        self::assertSame(['Judul B', ''], Parser::pisahJudul("\r\n#  Judul B  \r\n"));
        self::assertSame(['', "## bukan H1\nisi"], Parser::pisahJudul("## bukan H1\nisi"));
        self::assertSame(['', "teks\n# H1 di tengah\n"], Parser::pisahJudul("teks\n# H1 di tengah\n"));
    }

    public function testCrlfDiterima(): void
    {
        $md = str_replace("\n", "\r\n", self::fixture());
        $u = Parser::urai($md);

        self::assertSame([], $u['galat']);
        self::assertSame(2, count($u['mvc']));
        self::assertSame('1.2.0', $u['versi']);
    }

    public function testBlokDanFenceTerpisah(): void
    {
        $md = self::fixture();
        self::assertNull(Parser::blok($md, 'mvc:tidak_ada'));
        $isi = Parser::blok($md, 'app');
        self::assertNotNull($isi);
        self::assertNull(Parser::fence((string) $isi, Parser::FENCE_MVC), 'fence app bukan fence mvc');
        self::assertStringStartsWith('{', (string) Parser::fence((string) $isi, Parser::FENCE_APP));
    }
}
