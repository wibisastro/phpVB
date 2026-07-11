<?php

declare(strict_types=1);

namespace Gov2lib\Database;

use Gov2lib\Contracts\DatabaseInterface;

/**
 * DatabaseInterface implementation wrapping the MeekroDB static facade.
 *
 * Adapter tipis: koneksi & kredensial tetap dikelola dsnSource
 * (credentialDB men-set \DB::$user dst; MeekroDB connect lazy pada
 * pemanggilan pertama). Adapter ini tidak mengubah perilaku query
 * apa pun — hanya memberi seam interface untuk kode baru dan routing
 * internal crudModel (fase T2, Redmine gov2 #6085).
 */
class MeekroAdapter implements DatabaseInterface
{
    public function __construct()
    {
        \Gov2lib\dsnSource::requireMeekroDB();
    }

    public function query(string $sql, mixed ...$params): array
    {
        $result = \DB::query($sql, ...$params);

        return is_array($result) ? $result : [];
    }

    public function queryFirstRow(string $sql, mixed ...$params): ?array
    {
        return \DB::queryFirstRow($sql, ...$params);
    }

    public function queryFirstColumn(string $sql, mixed ...$params): array
    {
        return \DB::queryFirstColumn($sql, ...$params);
    }

    public function queryFirstField(string $sql, mixed ...$params): mixed
    {
        return \DB::queryFirstField($sql, ...$params);
    }

    public function insert(string $table, array $data): int
    {
        \DB::insert($table, $data);

        return (int) \DB::insertId();
    }

    public function update(string $table, array $data, string $where, mixed ...$params): int
    {
        \DB::update($table, $data, $where, ...$params);

        return (int) \DB::affectedRows();
    }

    public function delete(string $table, string $where, mixed ...$params): int
    {
        \DB::delete($table, $where, ...$params);

        return (int) \DB::affectedRows();
    }

    public function count(string $table, string $where = '', mixed ...$params): int
    {
        $sql = "SELECT COUNT(*) FROM {$table}" . ($where !== '' ? " WHERE {$where}" : '');

        return (int) \DB::queryFirstField($sql, ...$params);
    }

    public function startTransaction(): void
    {
        \DB::startTransaction();
    }

    public function commit(): void
    {
        \DB::commit();
    }

    public function rollback(): void
    {
        \DB::rollback();
    }

    public function insertId(): int
    {
        return (int) \DB::insertId();
    }

    public function affectedRows(): int
    {
        return (int) \DB::affectedRows();
    }

    public function columnList(string $table): array
    {
        return \DB::columnList($table);
    }
}
