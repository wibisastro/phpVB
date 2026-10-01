<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\Traits\FilterPanel;
use PHPUnit\Framework\TestCase;

class FilterPanelFakeWhereClause
{
    public string $type;
    public array $clauses = [];

    public function __construct(string $type)
    {
        $this->type = strtolower($type);
    }

    public function add(mixed $sql, mixed ...$args): void
    {
        if ($sql instanceof self) {
            $this->clauses[] = $sql;
            return;
        }
        $this->clauses[] = ['sql' => $sql, 'args' => $args];
    }

    public function textAndArgs(): array
    {
        $sql = [];
        $args = [];
        if (!$this->clauses) {
            return ['(1)', []];
        }
        foreach ($this->clauses as $clause) {
            if ($clause instanceof self) {
                [$clauseSql, $clauseArgs] = $clause->textAndArgs();
            } else {
                $clauseSql = $clause['sql'];
                $clauseArgs = $clause['args'];
            }
            $sql[] = "($clauseSql)";
            $args = array_merge($args, $clauseArgs);
        }
        $joined = $this->type === 'and' ? implode(' AND ', $sql) : implode(' OR ', $sql);
        return ["($joined)", $args];
    }
}

class FilterPanelDummy
{
    use FilterPanel;

    public string $className = 'metadata';

    /** @var array<int, array<string, mixed>> */
    public array $fields = [];

    protected function filterPanelFields(): array
    {
        return $this->fields;
    }

    protected function filterPanelWhereClause(string $type): object
    {
        return new FilterPanelFakeWhereClause($type);
    }
}

class FilterPanelTest extends TestCase
{
    private FilterPanelDummy $model;

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->model = new FilterPanelDummy();
        $this->model->fields = [
            ['name' => 'keyword', 'label' => 'Kata Kunci', 'type' => 'text',
                'where' => ['kolom' => 'parent.nama', 'op' => 'like']],
            ['name' => 'referensi', 'label' => 'Referensi', 'type' => 'select2-tags', 'multiple' => true,
                'where' => ['kolom' => 'parent.referensi_id', 'op' => 'find_in_set']],
            ['name' => 'type', 'label' => 'Type', 'type' => 'select2-tags', 'multiple' => true,
                'where' => ['kolom' => 'parent.type', 'op' => 'in']],
            ['name' => 'urusan', 'label' => 'Urusan', 'type' => 'select2',
                'where' => ['kolom' => 'parent.urusan_id', 'op' => '=', 'cast' => 'int']],
        ];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testApplyMenyimpanPerElemenHanyaFieldKnownTidakKosong(): void
    {
        $aktif = $this->model->filterApplyData('kab', [
            'keyword' => '  pajak  ',
            'type' => ['angka', 'formula'],
            'urusan' => '5',
            'asing' => 'dibuang',
            'kosong' => '',
        ]);

        $this->assertSame(3, $aktif);
        $this->assertSame(
            ['keyword' => 'pajak', 'type' => ['angka', 'formula'], 'urusan' => 5],
            $_SESSION['filter_global']
        );
    }

    public function testFieldsDataMembuangWhereDanMengembalikanNilaiAktif(): void
    {
        $this->model->filterApplyData('', ['keyword' => 'pajak']);

        $data = $this->model->filterFieldsData('');

        $this->assertSame(1, $data['aktif']);
        $this->assertSame(['keyword' => 'pajak'], $data['nilai']);
        foreach ($data['fields'] as $field) {
            $this->assertArrayNotHasKey('where', $field);
        }
    }

    public function testFilterWhereSemuaOpMemakaiPlaceholder(): void
    {
        $_SESSION['filter_global'] = [
            'keyword' => 'pajak',
            'referensi' => ['1', '2'],
            'type' => ['angka', 'formula'],
            'urusan' => 5,
        ];

        $where = $this->model->filterWhere('');
        [$sql, $args] = $where->textAndArgs();

        $this->assertStringContainsString('parent.nama LIKE %ss', $sql);
        $this->assertStringContainsString('parent.type IN %ls', $sql);
        $this->assertStringContainsString('parent.urusan_id = %i', $sql);
        $this->assertSame(2, substr_count($sql, 'FIND_IN_SET(%s, parent.referensi_id)'));
        $this->assertStringContainsString(' OR ', $sql);

        $this->assertContains('pajak', $args);
        $this->assertContains(['angka', 'formula'], $args);
        $this->assertContains(5, $args);
        $this->assertContains('1', $args);
        $this->assertContains('2', $args);
    }

    public function testMultipleInAdalahOrBukanAnd(): void
    {
        $_SESSION['filter_global'] = ['type' => ['angka', 'formula']];

        [$sql] = $this->model->filterWhere('')->textAndArgs();

        $this->assertStringContainsString('parent.type IN %ls', $sql);
        $this->assertStringNotContainsString("parent.type = 'angka' AND parent.type = 'formula'", $sql);
    }

    public function testKolomTidakValidDitolak(): void
    {
        $this->model->fields[] = ['name' => 'jahat', 'where' => ['kolom' => 'parent.nama; DROP TABLE member', 'op' => '=']];
        $_SESSION['filter_global'] = ['jahat' => 'x'];

        $this->expectException(\InvalidArgumentException::class);
        $this->model->filterWhere('');
    }

    public function testOpDiLuarWhitelistDitolak(): void
    {
        $this->model->fields[] = ['name' => 'jahat', 'where' => ['kolom' => 'parent.nama', 'op' => 'regexp']];
        $_SESSION['filter_global'] = ['jahat' => 'x'];

        $this->expectException(\InvalidArgumentException::class);
        $this->model->filterWhere('');
    }


    public function testLikeDenganDaftarKolomMenjadiOrPlaceholder(): void
    {
        $this->model->fields = [
            ['name' => 'keyword', 'type' => 'text',
                'where' => ['kolom' => ['t1.nama', 't1.kodeindikator'], 'op' => 'like']],
        ];
        $_SESSION['filter_global'] = ['keyword' => "x' OR 1=1"];

        [$sql, $args] = $this->model->filterWhere('')->textAndArgs();

        $this->assertSame('((((t1.nama LIKE %ss) OR (t1.kodeindikator LIKE %ss))))', $sql);
        $this->assertSame(["x' OR 1=1", "x' OR 1=1"], $args);
    }

    public function testDaftarKolomUntukOpLainDitolak(): void
    {
        $this->model->fields = [
            ['name' => 'type', 'type' => 'select', 'where' => ['kolom' => ['a', 'b'], 'op' => '=']],
        ];
        $_SESSION['filter_global'] = ['type' => 'x'];

        $this->expectException(\InvalidArgumentException::class);
        $this->model->filterWhere('');
    }

    public function testFilterValueTanpaDimensiInstance(): void
    {
        $this->model->filterApplyData('kab', ['keyword' => 'pajak']);

        $this->assertSame('pajak', $this->model->filterValue('keyword', 'kab'));
        $this->assertSame('pajak', $this->model->filterValue('keyword', 'kec'));   // instance diabaikan (filter global)
        $this->assertNull($this->model->filterValue('tidak_ada'));
    }

    // ---------------------------------------------------------------- F3b: filter global lintas MVC

    private function modelLain(string $className, array $fields): FilterPanelDummy
    {
        $m = new FilterPanelDummy();
        $m->className = $className;
        $m->fields = $fields;
        return $m;
    }

    public function testElemenBernamaSamaDiBagiAntarMvc(): void
    {
        $this->model->filterApplyData('', ['keyword' => 'pajak', 'type' => ['angka']]);

        $lain = $this->modelLain('terbuka', [
            ['name' => 'keyword', 'type' => 'text', 'where' => ['kolom' => 't1.nama', 'op' => 'like']],
            ['name' => 'jenis', 'type' => 'select', 'options' => [['id' => 'Spasial', 'text' => 'Spasial']],
                'where' => ['kolom' => 't1.jenis', 'op' => '=']],
        ]);

        $this->assertSame('pajak', $lain->filterValue('keyword'));
        $this->assertSame(1, count($lain->filterFieldsData()['nilai']));      // type tak ada di MVC lain → tak dihitung
        [$sql, $args] = $lain->filterWhere()->textAndArgs();
        $this->assertSame('((t1.nama LIKE %ss))', $sql);
        $this->assertSame(['pajak'], $args);
    }

    public function testApplyDiMvcIniTidakMenyentuhElemenMvcLain(): void
    {
        $this->model->filterApplyData('', ['urusan' => '5', 'keyword' => 'a']);
        $lain = $this->modelLain('terbuka', [
            ['name' => 'keyword', 'type' => 'text', 'where' => ['kolom' => 't1.nama', 'op' => 'like']],
        ]);
        $lain->filterApplyData('', ['keyword' => 'b']);

        $this->assertSame('b', $_SESSION['filter_global']['keyword']);
        $this->assertSame(5, $_SESSION['filter_global']['urusan']);   // milik MVC pertama, tak ada di terbuka
    }

    public function testApplyMengosongkanElemenMvcIniYangTidakDikirim(): void
    {
        $this->model->filterApplyData('', ['keyword' => 'a', 'type' => ['angka']]);
        $this->model->filterApplyData('', ['keyword' => 'a']);

        $this->assertArrayNotHasKey('type', $_SESSION['filter_global']);
        $this->assertSame('a', $_SESSION['filter_global']['keyword']);
    }

    public function testScopeMvcMemisahkanKunciDanKeyEksplisit(): void
    {
        $a = $this->modelLain('metadata', [
            ['name' => 'status', 'type' => 'text', 'scope' => 'mvc'],
            ['name' => 'kode', 'type' => 'text', 'key' => 'kode_khusus'],
        ]);
        $b = $this->modelLain('daftardata', [
            ['name' => 'status', 'type' => 'text', 'scope' => 'mvc'],
            ['name' => 'kode', 'type' => 'text', 'key' => 'kode_khusus'],
        ]);
        $a->filterApplyData('', ['status' => 'x', 'kode' => 'k1']);

        $this->assertNull($b->filterValue('status'));                 // scope mvc → tak dibagi
        $this->assertSame('k1', $b->filterValue('kode'));              // key eksplisit sama → dibagi
        $this->assertArrayHasKey('metadata.status', $_SESSION['filter_global']);
        $this->assertArrayHasKey('kode_khusus', $_SESSION['filter_global']);
    }

    public function testNilaiDiLuarOpsiStatisDiabaikanDiMvcTujuan(): void
    {
        $_SESSION['filter_global'] = ['jenis' => 'Keuangan', 'type' => ['angka', 'bukan_opsi']];
        $lain = $this->modelLain('terbuka', [
            ['name' => 'jenis', 'type' => 'select', 'options' => [['id' => 'Spasial', 'text' => 'Spasial']],
                'where' => ['kolom' => 'jenis', 'op' => '=']],
            ['name' => 'type', 'type' => 'select2-tags', 'options' => [['id' => 'angka', 'text' => 'Angka']],
                'where' => ['kolom' => 'type', 'op' => 'in']],
        ]);

        $this->assertNull($lain->filterValue('jenis'));
        $this->assertSame(['angka'], $lain->filterValue('type'));
        $this->assertSame(1, $lain->filterFieldsData()['aktif']);
    }

    public function testBadgeAktifHanyaMenghitungElemenYangAdaDiMvcIni(): void
    {
        $_SESSION['filter_global'] = ['keyword' => 'a', 'urusan' => 5, 'tidak_ada_di_json' => 'z'];
        $data = $this->model->filterFieldsData();

        $this->assertSame(2, $data['aktif']);
        $this->assertSame(['keyword' => 'a', 'urusan' => 5], $data['nilai']);
    }

    public function testResetMvcIniTidakMenghapusElemenMvcLainSedangResetAllMenghapusSemua(): void
    {
        $_SESSION['filter_global'] = ['keyword' => 'a', 'sisa_mvc_lain' => 'x'];
        $_SESSION['filter_global_teks'] = ['keyword' => ['a'], 'sisa_mvc_lain' => ['x']];

        $this->model->filterResetData();
        $this->assertSame(['sisa_mvc_lain' => 'x'], $_SESSION['filter_global']);
        $this->assertSame(['sisa_mvc_lain' => ['x']], $_SESSION['filter_global_teks']);

        $this->model->filterResetAllData();
        $this->assertArrayNotHasKey('filter_global', $_SESSION);
        $this->assertArrayNotHasKey('filter_global_teks', $_SESSION);
    }

    public function testChipMemakaiTeksTersimpanLaluTeksOpsiStatisLaluNilaiMentah(): void
    {
        $m = $this->modelLain('metadata', [
            ['name' => 'urusan', 'label' => 'Urusan', 'type' => 'select2', 'options_url' => 'x'],
            ['name' => 'jenis', 'label' => 'Jenis', 'type' => 'select',
                'options' => [['id' => 'Spasial', 'text' => 'Data Spasial']]],
            ['name' => 'keyword', 'label' => 'Kata Kunci', 'type' => 'text'],
        ]);
        $m->filterApplyData('', ['urusan' => '5', 'jenis' => 'Spasial', 'keyword' => 'pajak'],
            ['urusan' => ['URUSAN WAJIB'], 'keyword' => ['  ', str_repeat('x', 500)]]);

        $chips = array_column($m->filterPanelChips(), null, 'name');
        $this->assertSame(['URUSAN WAJIB'], $chips['urusan']['teks']);          // teks tersimpan dari klien
        $this->assertSame(['Data Spasial'], $chips['jenis']['teks']);           // teks opsi statis
        $this->assertSame(200, mb_strlen($chips['keyword']['teks'][0]));        // teks klien dipotong; kosong dibuang
        $this->assertSame('Urusan', $chips['urusan']['label']);
    }

    public function testKeyTidakValidDitolak(): void
    {
        $m = $this->modelLain('x', [['name' => 'a', 'type' => 'text', 'key' => "bad key'; DROP"]]);
        $this->expectException(\InvalidArgumentException::class);
        $m->filterApplyData('', ['a' => '1']);
    }
}
