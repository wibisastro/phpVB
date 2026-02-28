<?php

declare(strict_types=1);

namespace Gov2lib\Contracts;

/**
 * Database abstraction interface.
 *
 * This interface provides a contract for database implementations,
 * allowing seamless swapping between different database adapters
 * such as MeekroDB, PDO, Supabase, or other backends.
 *
 * @package Gov2lib\Contracts
 */
interface DatabaseInterface
{
    /**
     * Execute a query and return all rows.
     *
     * @param string $sql The SQL query string with optional placeholders.
     * @param mixed ...$params Query parameters to bind.
     * @return array Array of rows (associative arrays).
     */
    public function query(string $sql, mixed ...$params): array;

    /**
     * Execute a query and return the first row.
     *
     * @param string $sql The SQL query string with optional placeholders.
     * @param mixed ...$params Query parameters to bind.
     * @return array|null The first row or null if no rows found.
     */
    public function queryFirstRow(string $sql, mixed ...$params): ?array;

    /**
     * Execute a query and return the first column of all rows.
     *
     * @param string $sql The SQL query string with optional placeholders.
     * @param mixed ...$params Query parameters to bind.
     * @return array Array of values from the first column.
     */
    public function queryFirstColumn(string $sql, mixed ...$params): array;

    /**
     * Execute a query and return the first field of the first row.
     *
     * @param string $sql The SQL query string with optional placeholders.
     * @param mixed ...$params Query parameters to bind.
     * @return mixed The scalar value from the first field, or null.
     */
    public function queryFirstField(string $sql, mixed ...$params): mixed;

    /**
     * Insert a row into a table.
     *
     * @param string $table The table name.
     * @param array $data Associative array of column => value pairs.
     * @return int The ID of the inserted row.
     */
    public function insert(string $table, array $data): int;

    /**
     * Update rows in a table.
     *
     * @param string $table The table name.
     * @param array $data Associative array of column => value pairs to update.
     * @param string $where WHERE clause condition (e.g., "id = ?" or "status = ?").
     * @param mixed ...$params Parameters to bind in the WHERE clause.
     * @return int The number of affected rows.
     */
    public function update(string $table, array $data, string $where, mixed ...$params): int;

    /**
     * Delete rows from a table.
     *
     * @param string $table The table name.
     * @param string $where WHERE clause condition (e.g., "id = ?" or "status = ?").
     * @param mixed ...$params Parameters to bind in the WHERE clause.
     * @return int The number of affected rows.
     */
    public function delete(string $table, string $where, mixed ...$params): int;

    /**
     * Count rows in a table matching optional criteria.
     *
     * @param string $table The table name.
     * @param string $where Optional WHERE clause condition. Defaults to empty string for no filter.
     * @param mixed ...$params Parameters to bind in the WHERE clause.
     * @return int The count of matching rows.
     */
    public function count(string $table, string $where = '', mixed ...$params): int;

    /**
     * Start a database transaction.
     *
     * @return void
     */
    public function startTransaction(): void;

    /**
     * Commit the current transaction.
     *
     * @return void
     */
    public function commit(): void;

    /**
     * Rollback the current transaction.
     *
     * @return void
     */
    public function rollback(): void;

    /**
     * Get the ID of the last inserted row.
     *
     * @return int The last insert ID.
     */
    public function insertId(): int;

    /**
     * Get the number of rows affected by the last operation.
     *
     * @return int The number of affected rows.
     */
    public function affectedRows(): int;
}
