<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Skenario CRUD crudModel yang WAJIB berperilaku identik lintas-driver
 * (fase T4 #6085): suite yang sama dijalankan dua kali — driver meekro di
 * MySQL lokal (CrudModelMySQLTest) dan driver supabase di gajah
 * (CrudModelGajahTest). Bukti "ganti driver saja": tidak ada satu pun
 * test di kelas ini yang tahu driver apa yang sedang dipakainya.
 */
abstract class CrudModelParityBase extends TestCase
{
    /** @var array<int, array{0: string, 1: int}> pasangan [tabel, id] buatan test */
    protected array $created = [];

    /** pageID app fixture suite ini. */
    abstract protected function pageId(): string;

    /** Nama tabel flat (tanpa parent_id). */
    abstract protected function flatTable(): string;

    /** Nama tabel hierarkis (parent_id/level/level_label/children). */
    abstract protected function hierTable(): string;

    /** crudModel siap uji pada driver suite ini, tbl dipatok ke $table. */
    abstract protected function model(string $table): object;

    protected function setUp(): void
    {
        $GLOBALS['pageID'] = $this->pageId();
        $GLOBALS['config'] = new \SimpleXMLElement('<config/>');
        $GLOBALS['doc'] = new class {
            public $error = null;
            public function error(...$args): void
            {
                throw new \RuntimeException('doc->error dipanggil: ' . json_encode($args));
            }
            public function exceptionHandler(string $m): void
            {
                throw new \RuntimeException("exceptionHandler: {$m}");
            }
        };
    }

    /**
     * Harness crudModel: tbl dipatok ke tabel test, ses & formfield distub.
     * Driver mengikuti entri DSN fixture app (subclass boleh mengganti DSN
     * setelahnya via credentialDB).
     */
    protected function newHarnessModel(string $table): object
    {
        $model = new class extends \Gov2lib\crudModel {
            public function breadcrumbRows(): array
            {
                return $this->breadcrumb;
            }
            public function driverName(): string
            {
                return $this->driver;
            }
            public function setTable(string $table): void
            {
                $this->tbl = (object) ['table' => $table];
            }
        };

        $model->setTable($table);
        $ses = (new \ReflectionClass(\Gov2lib\gov2session::class))->newInstanceWithoutConstructor();
        $ses->val = ['account_id' => 7];
        $model->ses = $ses;
        $model->fields = [];
        $model->gov2formfield = new class {
            public function getLevel($fields, $level, $label)
            {
                return $label;
            }
        };

        return $model;
    }

    public function testCrudRoundtripFlat(): void
    {
        $model = $this->model($this->flatTable());

        $id = $model->doAdd(['nama' => 'Andi', 'status' => 'on']);
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
        $this->created[] = [$this->flatTable(), $id];

        $row = $model->doRead($id);
        $this->assertEquals('Andi', $row['nama']);
        $this->assertEquals(7, $row['created_by']);
        $this->assertNotEmpty($row['created_at']);

        $model->doUpdate(['id' => $id, 'nama' => 'Budi', 'cmd' => 'update']);
        $this->assertEquals('Budi', $model->doRead($id)['nama']);

        $model->doDel($id);
        $this->assertNull($model->doRead($id));
    }

    public function testHierarkiAddCountChildrenBrowse(): void
    {
        $model = $this->model($this->hierTable());

        $provId = $model->doAdd(['nama' => 'Jawa Barat', 'level' => '1', 'level_label' => 'prov']);
        $kabId = $model->doAdd(['nama' => 'Bogor', 'level' => '2', 'level_label' => 'kab', 'parent_id' => $provId]);
        $this->created[] = [$this->hierTable(), $provId];
        $this->created[] = [$this->hierTable(), $kabId];

        // Anak mewarisi kolom {parent.level_label}_id + counter children parent terupdate
        $kab = $model->doRead($kabId);
        $this->assertEquals($provId, $kab['prov_id']);
        $this->assertEquals($provId, $kab['parent_id']);
        $this->assertEquals(1, (int) $model->doRead($provId)['children']);

        $this->assertEquals(1, $model->doCountChildren($provId)['totalRecord']);

        $rows = $model->doBrowse(1, $provId);
        $this->assertCount(1, $rows);
        $this->assertEquals('Bogor', $rows[0]['nama']);

        // Browse dengan kolom parent custom ({name}_id)
        $rows = $model->doBrowse(1, $provId, 'prov');
        $this->assertCount(1, $rows);
    }

    public function testDelAnakMengoreksiChildrenParent(): void
    {
        $model = $this->model($this->hierTable());
        $provId = $model->doAdd(['nama' => 'Jabar', 'level' => '1', 'level_label' => 'prov']);
        $kabId = $model->doAdd(['nama' => 'Depok', 'level' => '2', 'level_label' => 'kab', 'parent_id' => $provId]);
        $this->created[] = [$this->hierTable(), $provId];
        $this->created[] = [$this->hierTable(), $kabId];

        $model->doDel($kabId);

        $this->assertEquals(0, (int) $model->doRead($provId)['children']);
        $this->assertEquals(0, $model->doCountChildren($provId)['totalRecord']);
    }

    public function testSetBreadcrumbMenelusuriParent(): void
    {
        $model = $this->model($this->hierTable());
        $provId = $model->doAdd(['nama' => 'Jabar', 'level' => '1', 'level_label' => 'prov']);
        $kabId = $model->doAdd(['nama' => 'Depok', 'level' => '2', 'level_label' => 'kab', 'parent_id' => $provId]);
        $this->created[] = [$this->hierTable(), $provId];
        $this->created[] = [$this->hierTable(), $kabId];

        $model->setBreadcrumb($kabId);
        $crumbs = array_column($model->breadcrumbRows(), 'caption');

        $this->assertEquals(['Depok', 'Jabar'], $crumbs);
    }

    /**
     * Dokumentasi perilaku existing (quirk parity): doUpdate memeriksa
     * in_array('parent_id', columnList) atas ASSOC array metadata — selalu
     * false, jadi jalur hierarkis tidak pernah aktif dan parent_id di-unset
     * dari update. Dipertahankan apa adanya di T2/T4 di SEMUA driver;
     * perbaikannya keputusan terpisah.
     */
    public function testDoUpdateQuirkParentIdTidakPernahHierarkis(): void
    {
        $model = $this->model($this->hierTable());
        $provId = $model->doAdd(['nama' => 'Jabar', 'level' => '1', 'level_label' => 'prov']);
        $kabId = $model->doAdd(['nama' => 'Depok', 'level' => '2', 'level_label' => 'kab', 'parent_id' => $provId]);
        $this->created[] = [$this->hierTable(), $provId];
        $this->created[] = [$this->hierTable(), $kabId];

        $model->doUpdate(['id' => $kabId, 'nama' => 'Depok Baru', 'parent_id' => 999]);

        $row = $model->doRead($kabId);
        $this->assertEquals('Depok Baru', $row['nama']);
        $this->assertEquals($provId, $row['parent_id'], 'parent_id di-unset oleh quirk — tidak ikut terupdate');
    }
}
