<?php

declare(strict_types=1);

namespace Gov2lib\Database;

use Gov2lib\Contracts\CrudRepositoryInterface;
use Gov2lib\Contracts\DatabaseInterface;

/**
 * CrudRepositoryInterface implementation on top of DatabaseInterface (MySQL).
 *
 * Nama tabel diasumsikan tepercaya (berasal dari dbTables.xml, bukan input
 * user) — konsisten dengan pola interpolasi di crudModel. Nilai filter
 * selalu dibind lewat placeholder MeekroDB (%s/%i), tidak diinterpolasi.
 */
class MeekroCrudRepository implements CrudRepositoryInterface
{
    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    public function add(string $table, array $data): ?int
    {
        $id = $this->db->insert($table, $data);

        return $id > 0 ? $id : null;
    }

    public function read(string $table, int $id): ?array
    {
        return $this->db->queryFirstRow("SELECT * FROM {$table} WHERE id=%i", $id);
    }

    public function update(string $table, array $data, int $id): int
    {
        return $this->db->update($table, $data, 'id=%i', $id);
    }

    public function delete(string $table, int $id): int
    {
        return $this->db->delete($table, 'id=%i', $id);
    }

    public function browse(
        string $table,
        array $filter = [],
        int $limit = 0,
        int $offset = 0,
        string $orderBy = ''
    ): array {
        [$where, $params] = $this->buildWhere($filter);

        $sql = "SELECT * FROM {$table}{$where}";

        if ($orderBy !== '') {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit > 0) {
            $sql .= ' LIMIT ' . max(0, $offset) . ',' . $limit;
        }

        return $this->db->query($sql, ...$params);
    }

    public function count(string $table, array $filter = []): int
    {
        [$where, $params] = $this->buildWhere($filter);

        return (int) $this->db->queryFirstField(
            "SELECT COUNT(*) FROM {$table}{$where}",
            ...$params
        );
    }

    public function columns(string $table): array
    {
        return array_keys($this->db->columnList($table));
    }

    /**
     * @return array{0: string, 1: array<int, mixed>} potongan " WHERE ..." + params
     */
    private function buildWhere(array $filter): array
    {
        if (empty($filter)) {
            return ['', []];
        }

        $parts = [];
        $params = [];

        foreach ($filter as $column => $value) {
            $parts[] = $column . (is_int($value) ? '=%i' : '=%s');
            $params[] = $value;
        }

        return [' WHERE ' . implode(' AND ', $parts), $params];
    }
}
