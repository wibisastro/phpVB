<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\Contracts\DatabaseInterface;
use Gov2lib\Database\MeekroCrudRepository;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MeekroCrudRepository (fase T2 3-tier)
 *
 * Menguji terjemahan operasi repository → panggilan DatabaseInterface:
 * SQL yang dibentuk, placeholder per tipe nilai, dan passthrough hasil.
 * DatabaseInterface dipalsukan (recording fake) — tanpa MySQL.
 */
class MeekroCrudRepositoryTest extends TestCase
{
    private FakeDb $db;
    private MeekroCrudRepository $repo;

    protected function setUp(): void
    {
        $this->db = new FakeDb();
        $this->repo = new MeekroCrudRepository($this->db);
    }

    public function testAddReturnsInsertId(): void
    {
        $this->db->insertIdToReturn = 42;

        $id = $this->repo->add('member', ['nama' => 'Andi']);

        $this->assertEquals(42, $id);
        $this->assertEquals(['member', ['nama' => 'Andi']], $this->db->calls[0][1]);
    }

    public function testAddReturnsNullWhenNoId(): void
    {
        $this->db->insertIdToReturn = 0;

        $this->assertNull($this->repo->add('member', ['nama' => 'Andi']));
    }

    public function testReadBuildsSelectById(): void
    {
        $this->db->rowToReturn = ['id' => 7, 'nama' => 'Budi'];

        $row = $this->repo->read('member', 7);

        $this->assertEquals('Budi', $row['nama']);
        $this->assertEquals(['SELECT * FROM member WHERE id=%i', [7]], $this->db->calls[0][1]);
    }

    public function testUpdateAndDeleteById(): void
    {
        $this->repo->update('member', ['nama' => 'Cici'], 7);
        $this->repo->delete('member', 8);

        $this->assertEquals(['member', ['nama' => 'Cici'], 'id=%i', [7]], $this->db->calls[0][1]);
        $this->assertEquals(['member', 'id=%i', [8]], $this->db->calls[1][1]);
    }

    public function testBrowsePlain(): void
    {
        $this->repo->browse('member');

        $this->assertEquals(['SELECT * FROM member', []], $this->db->calls[0][1]);
    }

    public function testBrowseFilterTypedPlaceholdersOrderLimit(): void
    {
        $this->repo->browse(
            'member',
            ['status' => 'on', 'parent_id' => 3],
            limit: 50,
            offset: 100,
            orderBy: 'id DESC'
        );

        $this->assertEquals(
            ['SELECT * FROM member WHERE status=%s AND parent_id=%i ORDER BY id DESC LIMIT 100,50', ['on', 3]],
            $this->db->calls[0][1]
        );
    }

    public function testCountWithFilter(): void
    {
        $this->db->fieldToReturn = '5';

        $total = $this->repo->count('member', ['parent_id' => 3]);

        $this->assertSame(5, $total);
        $this->assertEquals(
            ['SELECT COUNT(*) FROM member WHERE parent_id=%i', [3]],
            $this->db->calls[0][1]
        );
    }

    public function testColumnsReturnsNamesOnly(): void
    {
        $this->db->columnsToReturn = [
            'id' => ['type' => 'int'],
            'parent_id' => ['type' => 'int'],
            'nama' => ['type' => 'varchar'],
        ];

        $this->assertEquals(['id', 'parent_id', 'nama'], $this->repo->columns('member'));
    }
}

/**
 * Recording fake untuk DatabaseInterface — mencatat (method, args).
 */
class FakeDb implements DatabaseInterface
{
    /** @var array<int, array{0: string, 1: array}> */
    public array $calls = [];
    public int $insertIdToReturn = 1;
    public ?array $rowToReturn = null;
    public mixed $fieldToReturn = null;
    public array $columnsToReturn = [];

    public function query(string $sql, mixed ...$params): array
    {
        $this->calls[] = ['query', [$sql, $params]];
        return [];
    }

    public function queryFirstRow(string $sql, mixed ...$params): ?array
    {
        $this->calls[] = ['queryFirstRow', [$sql, $params]];
        return $this->rowToReturn;
    }

    public function queryFirstColumn(string $sql, mixed ...$params): array
    {
        $this->calls[] = ['queryFirstColumn', [$sql, $params]];
        return [];
    }

    public function queryFirstField(string $sql, mixed ...$params): mixed
    {
        $this->calls[] = ['queryFirstField', [$sql, $params]];
        return $this->fieldToReturn;
    }

    public function insert(string $table, array $data): int
    {
        $this->calls[] = ['insert', [$table, $data]];
        return $this->insertIdToReturn;
    }

    public function update(string $table, array $data, string $where, mixed ...$params): int
    {
        $this->calls[] = ['update', [$table, $data, $where, $params]];
        return 1;
    }

    public function delete(string $table, string $where, mixed ...$params): int
    {
        $this->calls[] = ['delete', [$table, $where, $params]];
        return 1;
    }

    public function count(string $table, string $where = '', mixed ...$params): int
    {
        $this->calls[] = ['count', [$table, $where, $params]];
        return 0;
    }

    public function startTransaction(): void
    {
        $this->calls[] = ['startTransaction', []];
    }

    public function commit(): void
    {
        $this->calls[] = ['commit', []];
    }

    public function rollback(): void
    {
        $this->calls[] = ['rollback', []];
    }

    public function insertId(): int
    {
        return $this->insertIdToReturn;
    }

    public function affectedRows(): int
    {
        return 1;
    }

    public function columnList(string $table): array
    {
        $this->calls[] = ['columnList', [$table]];
        return $this->columnsToReturn;
    }
}
