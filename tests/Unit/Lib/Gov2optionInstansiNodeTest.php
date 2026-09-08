<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use App\gov2option\model\index as InstansiModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit test helper murni daftar instansi konteks-sensitif (gov2option).
 *
 * Yang diuji hanya fungsi statis tanpa DB/sesi:
 * - normSlug/normScope  : gerbang validasi sebelum slug masuk SQL & sesi
 * - parseNode           : node portal virtual "kl:{slug}" dari query string
 * - klNode/decorateRow  : bentuk JSON node (field lama WAJIB tetap ada)
 * - subOrgTerm          : sebutan sub-organisasi untuk label panel
 *
 * Rujukan desain: rencana/filter-konteks-instansi-wilayah.md (bagian 3.5).
 */
class Gov2optionInstansiNodeTest extends TestCase
{
    // ---------------------------------------------------------------- normSlug

    #[DataProvider('slugSah')]
    public function testNormSlugMenerimaSlugSah(string $masuk, string $harap): void
    {
        $this->assertSame($harap, InstansiModel::normSlug($masuk));
    }

    public static function slugSah(): array
    {
        return [
            'apa adanya' => ['kemenkeu', 'kemenkeu'],
            'huruf besar' => ['KemenPANRB', 'kemenpanrb'],
            'spasi tepi' => ['  jabarprov  ', 'jabarprov'],
            'garis bawah' => ['deputi_rb_aap', 'deputi_rb_aap'],
            'strip' => ['bangli-kab', 'bangli-kab'],
            'angka' => ['kl2', 'kl2'],
        ];
    }

    #[DataProvider('slugTakSah')]
    public function testNormSlugMenolakNilaiTakSah(mixed $masuk): void
    {
        $this->assertSame('', InstansiModel::normSlug($masuk));
    }

    public static function slugTakSah(): array
    {
        return [
            'kosong' => [''],
            'path traversal' => ['../etc'],
            'garis miring' => ['kemenkeu/itjen'],
            'spasi tengah' => ['kemen keu'],
            'kutip sql' => ["kemenkeu' OR 1=1 --"],
            'persen meekro' => ['%s'],
            'titik' => ['kemenkeu.go.id'],
            'array' => [['kemenkeu']],
            'null' => [null],
        ];
    }

    // --------------------------------------------------------------- normScope

    public function testNormScopeHanyaMenerimaScopeTerdaftar(): void
    {
        $this->assertSame('kl', InstansiModel::normScope('kl'));
        $this->assertSame('prov', InstansiModel::normScope(' PROV '));
        $this->assertSame('kab', InstansiModel::normScope('kab'));
        $this->assertSame('kota', InstansiModel::normScope('kota'));
    }

    /**
     * scope_level 'pusat' dipakai dashboard_ingest, BUKAN nama DB portal —
     * jangan sampai lolos jadi prefiks DB.
     */
    public function testNormScopeMenolakScopeAsing(): void
    {
        $this->assertSame('', InstansiModel::normScope('pusat'));
        $this->assertSame('', InstansiModel::normScope('desa'));
        $this->assertSame('', InstansiModel::normScope(''));
        $this->assertSame('', InstansiModel::normScope(['kl']));
        $this->assertSame('', InstansiModel::normScope(null));
    }

    // --------------------------------------------------------------- parseNode

    public function testParseNodePortalVirtual(): void
    {
        $this->assertSame(
            ['scope' => 'kl', 'slug' => 'kemenkeu'],
            InstansiModel::parseNode('kl:kemenkeu')
        );
        $this->assertSame(
            ['scope' => 'prov', 'slug' => 'jabarprov'],
            InstansiModel::parseNode('PROV:JabarProv')
        );
    }

    #[DataProvider('nodeTakSah')]
    public function testParseNodeTakSahJadiKosong(mixed $masuk): void
    {
        $this->assertSame(['scope' => '', 'slug' => ''], InstansiModel::parseNode($masuk));
    }

    public static function nodeTakSah(): array
    {
        return [
            'kosong' => [''],
            'tanpa pemisah' => ['kemenkeu'],
            'scope asing' => ['pusat:kemenkeu'],
            'slug kosong' => ['kl:'],
            'scope kosong' => [':kemenkeu'],
            'traversal' => ['kl:../../etc'],
            'injeksi' => ["kl:kemenkeu'--"],
            'array' => [['kl:kemenkeu']],
            'null' => [null],
        ];
    }

    /** Segmen setelah pemisah pertama tetap utuh → tetap ditolak, tidak terpotong. */
    public function testParseNodePemisahGandaTidakMemotongSlug(): void
    {
        $this->assertSame(['scope' => '', 'slug' => ''], InstansiModel::parseNode('kl:kemenkeu:itjen'));
    }

    // ------------------------------------------------------------------ klNode

    public function testKlNodeMemuatFieldLamaDanTambahan(): void
    {
        $node = InstansiModel::klNode('kemenkeu', 'Kementerian Keuangan');

        // field lama — dipakai frontend & pemakai JSON sebelum ada konteks
        foreach (['id', 'parent_id', 'level', 'nama', 'kode', 'portal', 'has_children'] as $key) {
            $this->assertArrayHasKey($key, $node, "field lama '{$key}' hilang dari node");
        }

        $this->assertSame(0, $node['id']);
        $this->assertSame(0, $node['parent_id']);
        $this->assertSame(0, $node['level']);
        $this->assertSame('Kementerian Keuangan', $node['nama']);
        $this->assertSame('kemenkeu', $node['kode']);
        $this->assertSame('kemenkeu', $node['portal']);
        $this->assertTrue($node['has_children']);

        // tambahan aditif
        $this->assertSame('kl:kemenkeu', $node['node']);
        $this->assertSame('kemenkeu', $node['slug']);
        $this->assertSame('kl', $node['scope']);
        $this->assertTrue($node['is_portal']);
    }

    /** scope_name NULL untuk 99 K/L → nama jatuh ke slug (COALESCE di SQL + pagar di PHP). */
    public function testKlNodeNamaKosongJatuhKeSlug(): void
    {
        $node = InstansiModel::klNode('lkpp', '');

        $this->assertSame('lkpp', $node['nama']);
    }

    // ------------------------------------------------------------- decorateRow

    public function testDecorateRowMempertahankanFieldLama(): void
    {
        $row = InstansiModel::decorateRow(
            ['id' => '24840', 'parent_id' => '10', 'level' => '2', 'nama' => 'Itjen', 'kode' => '01', 'portal' => 'itjen'],
            'kemenkeu',
            'kl'
        );

        $this->assertSame(24840, $row['id']);
        $this->assertSame(10, $row['parent_id']);
        $this->assertSame('2', $row['level']);
        $this->assertSame('Itjen', $row['nama']);
        $this->assertSame('01', $row['kode']);
        $this->assertSame('itjen', $row['portal']);

        $this->assertSame('kemenkeu', $row['slug']);
        $this->assertSame('kl', $row['scope']);
        $this->assertFalse($row['is_portal']);
        $this->assertSame('', $row['node']);
    }

    public function testDecorateRowBarisKosongTidakFatal(): void
    {
        $row = InstansiModel::decorateRow([], 'jabarprov', 'prov');

        $this->assertSame(0, $row['id']);
        $this->assertSame(0, $row['parent_id']);
        $this->assertFalse($row['is_portal']);
    }

    // -------------------------------------------------------------- subOrgTerm

    public function testSubOrgTermUnitKerjaUntukKlDanSupervisorTanpaKonteks(): void
    {
        // konteks K/L (pusat) → unit kerja
        $this->assertSame('Unit Kerja', InstansiModel::subOrgTerm(true, 'kl', true));
        $this->assertSame('Unit Kerja', InstansiModel::subOrgTerm(false, 'kl', true));
        // supervisor tanpa konteks → daftar = registry K/L
        $this->assertSame('Unit Kerja', InstansiModel::subOrgTerm(true, '', false));
    }

    public function testSubOrgTermOpdUntukDaerahDanPortalBiasa(): void
    {
        $this->assertSame('OPD', InstansiModel::subOrgTerm(true, 'prov', true));
        $this->assertSame('OPD', InstansiModel::subOrgTerm(true, 'kab', true));
        $this->assertSame('OPD', InstansiModel::subOrgTerm(true, 'kota', true));
        // portal non-supervisor tanpa konteks = perilaku lama
        $this->assertSame('OPD', InstansiModel::subOrgTerm(false, '', false));
    }
}
