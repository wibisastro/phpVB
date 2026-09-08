<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use App\gov2wilayah\sidepanel;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests untuk logika murni panel Wilayah (mode supervisor).
 *
 * Kasus:
 * - level_label → scope portal: provinsi→prov; kabupaten→kab/kota lewat registry,
 *   fallback akhiran slug; kecamatan/kelurahan/negara → '' (tidak punya portal)
 * - slug kosong / nilai berspasi & huruf besar dinormalkan
 * - clearInstansi() menulis null (bukan unset) pada 7 key instansi legacy,
 *   supaya array_merge di sesSave benar-benar menimpa token lama
 * - clearInstansi() tidak menyentuh key wilayah/ctx lain
 */
class WilayahContextTest extends TestCase
{
    // (a) provinsi

    public function testProvinsiSelaluScopeProv(): void
    {
        $this->assertSame('prov', sidepanel::scopeForWilayah('provinsi', 'jabarprov'));
        // Registry tidak dikonsultasikan untuk provinsi.
        $this->assertSame('prov', sidepanel::scopeForWilayah('provinsi', 'jabarprov', 'kota'));
    }

    // (b) kabupaten: registry menang

    public function testKabupatenMemakaiScopeLevelRegistry(): void
    {
        $this->assertSame('kab', sidepanel::scopeForWilayah('kabupaten', 'banglikab', 'kab'));
        $this->assertSame('kota', sidepanel::scopeForWilayah('kabupaten', 'bandungkota', 'kota'));
    }

    public function testRegistryNilaiAsingDiabaikan(): void
    {
        // scope_level di luar kab/kota (mis. 'kl' atau baris hilang) → fallback slug.
        $this->assertSame('kab', sidepanel::scopeForWilayah('kabupaten', 'banglikab', 'kl'));
        $this->assertSame('kota', sidepanel::scopeForWilayah('kabupaten', 'bandungkota', ''));
    }

    // (c) kabupaten tanpa registry: fallback akhiran slug

    public function testFallbackAkhiranSlugKota(): void
    {
        $this->assertSame('kota', sidepanel::scopeForWilayah('kabupaten', 'depokkota'));
        $this->assertSame('kab', sidepanel::scopeForWilayah('kabupaten', 'bogorkab'));
        // Tanpa akhiran yang dikenal tetap kab (mayoritas 416 kab vs 99 kota).
        $this->assertSame('kab', sidepanel::scopeForWilayah('kabupaten', 'sleman'));
    }

    // (d) level tanpa portal

    public function testKecamatanKelurahanNegaraTidakPunyaPortal(): void
    {
        $this->assertSame('', sidepanel::scopeForWilayah('kecamatan', 'banglikab'));
        $this->assertSame('', sidepanel::scopeForWilayah('kelurahan', 'banglikab'));
        $this->assertSame('', sidepanel::scopeForWilayah('negara', 'indonesia'));
        $this->assertSame('', sidepanel::scopeForWilayah('', 'banglikab'));
    }

    public function testSlugKosongTidakPernahJadiKonteks(): void
    {
        $this->assertSame('', sidepanel::scopeForWilayah('provinsi', ''));
        $this->assertSame('', sidepanel::scopeForWilayah('kabupaten', '   ', 'kota'));
    }

    // (e) normalisasi

    public function testNilaiBerspasiDanHurufBesarDinormalkan(): void
    {
        $this->assertSame('prov', sidepanel::scopeForWilayah(' Provinsi ', 'jabarprov'));
        $this->assertSame('kota', sidepanel::scopeForWilayah('KABUPATEN', 'bandungkota', ' KOTA '));
        $this->assertSame('kota', sidepanel::scopeForWilayah('kabupaten', 'BandungKota'));
    }

    // (f) reset key instansi legacy

    public function testClearInstansiMenulisNullBukanUnset(): void
    {
        $val = [
            'opd_id' => 24840,
            'opd' => 'bappeda',
            'portal_nama' => 'Bappeda',
            'unit_nama' => 'Bappeda',
            'unit_id' => 24840,
            'unit_parent_id' => 0,
            'change_portal' => 1,
        ];

        sidepanel::clearInstansi($val);

        foreach (array_keys($val) as $key) {
            $this->assertArrayHasKey($key, $val, "key {$key} harus tetap ada (null, bukan unset)");
            $this->assertNull($val[$key]);
        }
    }

    public function testClearInstansiMengisiKeyYangBelumAda(): void
    {
        $val = [];

        sidepanel::clearInstansi($val);

        $this->assertSame(
            ['opd_id', 'opd', 'portal_nama', 'unit_nama', 'unit_id', 'unit_parent_id', 'change_portal'],
            array_keys($val)
        );
    }

    public function testClearInstansiTidakMenyentuhKeyLain(): void
    {
        $val = [
            'userRole' => 'webmaster',
            'wilayah_id' => 32,
            'wilayah_nama' => 'JAWA BARAT',
            'ctx_scope' => 'prov',
            'ctx_slug' => 'jabarprov',
            'opd_id' => 99,
        ];

        sidepanel::clearInstansi($val);

        $this->assertSame('webmaster', $val['userRole']);
        $this->assertSame(32, $val['wilayah_id']);
        $this->assertSame('JAWA BARAT', $val['wilayah_nama']);
        $this->assertSame('prov', $val['ctx_scope']);
        $this->assertSame('jabarprov', $val['ctx_slug']);
        $this->assertNull($val['opd_id']);
    }
}
