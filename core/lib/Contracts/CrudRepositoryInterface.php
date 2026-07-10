<?php

declare(strict_types=1);

namespace Gov2lib\Contracts;

/**
 * Row-level CRUD repository contract.
 *
 * Kontrak WAJIB untuk semua adapter datasource (keputusan T0, Redmine gov2
 * #6085): operasinya sengaja dibatasi ke bentuk yang bisa dipetakan bersih
 * ke SQL (MeekroDB) MAUPUN REST (Supabase/PostgREST). Kode app baru
 * dianjurkan bicara ke interface ini; DatabaseInterface (SQL-level) hanya
 * dijamin penuh oleh adapter MySQL.
 *
 * Filter adalah array asosiatif kolom => nilai yang digabung AND dengan
 * perbandingan sama-dengan — bentuk yang setara dengan query-param `eq.`
 * di PostgREST.
 *
 * @package Gov2lib\Contracts
 */
interface CrudRepositoryInterface
{
    /**
     * Insert a row. Returns the new row id, or null on failure.
     */
    public function add(string $table, array $data): ?int;

    /**
     * Read a single row by id. Null when not found.
     */
    public function read(string $table, int $id): ?array;

    /**
     * Update a row by id. Returns the number of affected rows.
     */
    public function update(string $table, array $data, int $id): int;

    /**
     * Delete a row by id. Returns the number of affected rows.
     */
    public function delete(string $table, int $id): int;

    /**
     * Browse rows matching an equality filter.
     *
     * @param array  $filter  column => value, digabung AND
     * @param int    $limit   0 = tanpa limit
     * @param int    $offset  baris awal (hanya dipakai bila $limit > 0)
     * @param string $orderBy nama kolom + arah opsional, mis. "id DESC";
     *                        string kosong = urutan default backend
     * @return array<int, array<string, mixed>>
     */
    public function browse(
        string $table,
        array $filter = [],
        int $limit = 0,
        int $offset = 0,
        string $orderBy = ''
    ): array;

    /**
     * Count rows matching an equality filter.
     */
    public function count(string $table, array $filter = []): int;

    /**
     * List column names of a table.
     *
     * @return string[]
     */
    public function columns(string $table): array;
}
