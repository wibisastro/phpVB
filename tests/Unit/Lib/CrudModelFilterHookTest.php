<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\Traits\FilterPanel;
use PHPUnit\Framework\TestCase;

/** Hook filter di crudModel: aktif hanya bila model memakai trait FilterPanel. */
class CrudModelFilterHookTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // db.class.php MeekroDB melempar saat dimuat tanpa ekstensi mysqli, tapi class
        // WhereClause sudah terdefinisi sebelum itu — cukup dipanaskan sekali di sini.
        try {
            class_exists(\WhereClause::class);
        } catch (\Throwable) {
        }
    }

    protected function setUp(): void
    {
        if (!class_exists(\WhereClause::class, false)) {
            $this->markTestSkipped('MeekroDB WhereClause tidak termuat');
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    private function model(bool $withTrait, bool $repo = false): object
    {
        $calls = new \ArrayObject();
        $fake = $this->createMock(\Gov2lib\Contracts\DatabaseInterface::class);
        $fake->method('query')->willReturnCallback(function (string $sql, mixed ...$args) use ($calls): array {
            $calls[] = [$sql, $args];
            return [];
        });
        $fake->method('queryFirstRow')->willReturnCallback(function (string $sql, mixed ...$args) use ($calls): array {
            $calls[] = [$sql, $args];
            return ['totalRecord' => 0];
        });
        $fields = [
            ['name' => 'type', 'type' => 'select', 'where' => ['kolom' => 'type', 'op' => '=']],
        ];

        if ($withTrait) {
            $model = new class ($fake, $calls, $repo, $fields) extends \Gov2lib\crudModel {
                use FilterPanel;

                public string $className = 'uji';

                public function __construct(public \Gov2lib\Contracts\DatabaseInterface $fake, public \ArrayObject $calls, private bool $repoMode, private array $fieldDefs)
                {
                    $this->tbl = (object) ['table' => 'aset'];
                }

                protected function usesRepo(): bool
                {
                    return $this->repoMode;
                }

                public function db(): \Gov2lib\Contracts\DatabaseInterface
                {
                    return $this->fake;
                }

                public function exceptionHandler(string $e): void
                {
                    $this->errors[] = $e;
                }

                public array $errors = [];

                protected function filterPanelFields(): array
                {
                    return $this->fieldDefs;
                }
            };
        } else {
            $model = new class ($fake, $calls, $repo) extends \Gov2lib\crudModel {
                public array $errors = [];

                public function __construct(public \Gov2lib\Contracts\DatabaseInterface $fake, public \ArrayObject $calls, private bool $repoMode)
                {
                    $this->tbl = (object) ['table' => 'aset'];
                }

                protected function usesRepo(): bool
                {
                    return $this->repoMode;
                }

                public function db(): \Gov2lib\Contracts\DatabaseInterface
                {
                    return $this->fake;
                }

                public function exceptionHandler(string $e): void
                {
                    $this->errors[] = $e;
                }
            };
        }

        return $model;
    }

    private function sql(object $model): array
    {
        [$sql, $args] = $model->calls[0];
        $where = $args[0];
        [$text, $params] = $where->textAndArgs();

        return [$sql, $text, $params];
    }

    public function testBrowseTanpaTraitPerilakuLama(): void
    {
        $model = $this->model(false);
        $model->doBrowse(1, 0);

        [$sql, $text, $params] = $this->sql($model);
        $this->assertStringContainsString('WHERE %l', $sql);
        $this->assertSame('(1)', $text);
        $this->assertSame([], $params);
    }

    public function testBrowseTanpaTraitDenganParentTetapMemakaiParent(): void
    {
        $model = $this->model(false);
        $model->doBrowse(1, 7);

        [, $text, $params] = $this->sql($model);
        $this->assertSame('((parent_id=%i))', $text);
        $this->assertSame([7], $params);
    }

    public function testBrowseDenganTraitMenambahkanKlausaFilter(): void
    {
        $model = $this->model(true);
        $_SESSION['filter']['uji'] = ['type' => 'angka'];
        $model->doBrowse(1, 7);

        [, $text, $params] = $this->sql($model);
        $this->assertStringContainsString('parent_id=%i', $text);
        $this->assertStringContainsString('type = %s', $text);
        $this->assertSame([7, 'angka'], $params);
    }

    public function testCountDenganTraitMenambahkanKlausaFilter(): void
    {
        $model = $this->model(true);
        $_SESSION['filter']['uji'] = ['type' => 'angka'];
        $model->doCountChildren(0);

        [$sql, $text, $params] = $this->sql($model);
        $this->assertStringContainsString('count(id)', $sql);
        $this->assertStringContainsString('type = %s', $text);
        $this->assertSame(['angka'], $params);
    }

    public function testCountTanpaFilterAktifTidakMenambahSyarat(): void
    {
        $model = $this->model(true);
        $model->doCountChildren(0);

        [, , $params] = $this->sql($model);
        $this->assertSame([], $params);
    }

    public function testSupabaseDenganFilterAktifDitolakEksplisit(): void
    {
        $model = $this->model(true, true);
        $_SESSION['filter']['uji'] = ['type' => 'angka'];

        $this->assertNull($model->doBrowse(1, 0));
        $this->assertNull($model->doCountChildren(0));
        $this->assertCount(2, $model->errors);
        $this->assertStringContainsString('supabase', $model->errors[0]);
    }
}
