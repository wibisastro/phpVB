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

    public function testApplyMenyimpanHanyaFieldKnownTidakKosongPerInstance(): void
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
            $_SESSION['filter']['metadatakab']
        );

        $this->model->filterApplyData('kec', ['keyword' => 'lain']);
        $this->assertSame(['keyword' => 'lain'], $_SESSION['filter']['metadatakec']);
        $this->assertSame('pajak', $_SESSION['filter']['metadatakab']['keyword']);
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
        $_SESSION['filter']['metadata'] = [
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
        $_SESSION['filter']['metadata'] = ['type' => ['angka', 'formula']];

        [$sql] = $this->model->filterWhere('')->textAndArgs();

        $this->assertStringContainsString('parent.type IN %ls', $sql);
        $this->assertStringNotContainsString("parent.type = 'angka' AND parent.type = 'formula'", $sql);
    }

    public function testKolomTidakValidDitolak(): void
    {
        $this->model->fields[] = ['name' => 'jahat', 'where' => ['kolom' => 'parent.nama; DROP TABLE member', 'op' => '=']];
        $_SESSION['filter']['metadata'] = ['jahat' => 'x'];

        $this->expectException(\InvalidArgumentException::class);
        $this->model->filterWhere('');
    }

    public function testOpDiLuarWhitelistDitolak(): void
    {
        $this->model->fields[] = ['name' => 'jahat', 'where' => ['kolom' => 'parent.nama', 'op' => 'regexp']];
        $_SESSION['filter']['metadata'] = ['jahat' => 'x'];

        $this->expectException(\InvalidArgumentException::class);
        $this->model->filterWhere('');
    }

    public function testResetMenghapusHanyaInstanceItu(): void
    {
        $this->model->filterApplyData('kab', ['keyword' => 'a']);
        $this->model->filterApplyData('kec', ['keyword' => 'b']);

        $this->model->filterResetData('kab');

        $this->assertArrayNotHasKey('metadatakab', $_SESSION['filter']);
        $this->assertSame(['keyword' => 'b'], $_SESSION['filter']['metadatakec']);
    }

    public function testLikeDenganDaftarKolomMenjadiOrPlaceholder(): void
    {
        $this->model->fields = [
            ['name' => 'keyword', 'type' => 'text',
                'where' => ['kolom' => ['t1.nama', 't1.kodeindikator'], 'op' => 'like']],
        ];
        $_SESSION['filter']['metadata'] = ['keyword' => "x' OR 1=1"];

        [$sql, $args] = $this->model->filterWhere('')->textAndArgs();

        $this->assertSame('((((t1.nama LIKE %ss) OR (t1.kodeindikator LIKE %ss))))', $sql);
        $this->assertSame(["x' OR 1=1", "x' OR 1=1"], $args);
    }

    public function testDaftarKolomUntukOpLainDitolak(): void
    {
        $this->model->fields = [
            ['name' => 'type', 'type' => 'select', 'where' => ['kolom' => ['a', 'b'], 'op' => '=']],
        ];
        $_SESSION['filter']['metadata'] = ['type' => 'x'];

        $this->expectException(\InvalidArgumentException::class);
        $this->model->filterWhere('');
    }

    public function testFilterValueMembacaNilaiAktifPerInstance(): void
    {
        $this->model->filterApplyData('kab', ['keyword' => 'pajak']);

        $this->assertSame('pajak', $this->model->filterValue('keyword', 'kab'));
        $this->assertNull($this->model->filterValue('keyword', 'kec'));
    }
}
