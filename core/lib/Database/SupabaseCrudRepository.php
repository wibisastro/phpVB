<?php

declare(strict_types=1);

namespace Gov2lib\Database;

use Gov2lib\Contracts\CrudRepositoryInterface;

/**
 * CrudRepositoryInterface implementation di atas Supabase/PostgREST.
 *
 * Kontrak wajib lintas-tier (keputusan T0 #6085) untuk driver supabase:
 * semua operasi dipetakan langsung ke request REST oleh SupabaseAdapter,
 * tanpa SQL sama sekali. Identitas baris memakai kolom `id` — konsisten
 * dengan MeekroCrudRepository.
 */
class SupabaseCrudRepository implements CrudRepositoryInterface
{
    public function __construct(private readonly SupabaseAdapter $db)
    {
    }

    public function add(string $table, array $data): ?int
    {
        $row = $this->db->restInsert($table, $data);
        $id = (int) ($row['id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    public function read(string $table, int $id): ?array
    {
        return $this->db->restSelect($table, ['id' => $id], 1)[0] ?? null;
    }

    public function update(string $table, array $data, int $id): int
    {
        return $this->db->restUpdate($table, $data, ['id' => $id]);
    }

    public function delete(string $table, int $id): int
    {
        return $this->db->restDelete($table, ['id' => $id]);
    }

    public function browse(
        string $table,
        array $filter = [],
        int $limit = 0,
        int $offset = 0,
        string $orderBy = ''
    ): array {
        return $this->db->restSelect($table, $filter, $limit, $offset, $orderBy);
    }

    public function count(string $table, array $filter = []): int
    {
        return $this->db->restCount($table, $filter);
    }

    public function columns(string $table): array
    {
        return array_keys($this->db->columnList($table));
    }
}
