<?php

declare(strict_types=1);

namespace Gov2lib\Database;

use Gov2lib\Contracts\DatabaseInterface;
use Gov2lib\Exceptions\SupabaseException;
use Gov2lib\Exceptions\UnsupportedDriverOperationException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * DatabaseInterface adapter untuk Supabase/PostgREST (fase T3 #6085).
 *
 * Datasource tier 3: CRUD lewat REST API PostgREST, tanpa koneksi database
 * langsung. Operasi level-baris (insert/update/delete/count/select-filter)
 * dipetakan ke request HTTP; operasi SQL-level yang tidak punya padanan
 * REST (raw query, transaksi) melempar UnsupportedDriverOperationException
 * — kode app dianjurkan bicara CrudRepositoryInterface (repo()).
 *
 * Pemetaan operasi:
 *   select → GET    /rest/v1/{table}?{kolom}=eq.{nilai}&limit&offset&order
 *   insert → POST   /rest/v1/{table}          (Prefer: return=representation)
 *   update → PATCH  /rest/v1/{table}?{filter} (Prefer: return=representation)
 *   delete → DELETE /rest/v1/{table}?{filter} (Prefer: return=representation)
 *   count  → HEAD   /rest/v1/{table}?{filter} (Prefer: count=exact → Content-Range)
 *
 * Fragmen WHERE equality gaya MeekroDB ("id=%i", "a=%i AND b=%s") tetap
 * diterjemahkan ke filter eq. supaya jalur crudModel yang sudah dirouting
 * ke db() (fase T2) bisa dipetakan; bentuk WHERE lain dilempar sebagai
 * unsupported, bukan diterjemahkan setengah benar.
 */
class SupabaseAdapter implements DatabaseInterface
{
    private ClientInterface $client;

    /** Base URL PostgREST, selalu berakhiran '/rest/v1/'. */
    private string $base;
    private string $key;
    private string $schema;

    private int $lastInsertId = 0;
    private int $lastAffected = 0;

    /** @var array<string, array{properties?: array<string, array<string, mixed>>}>|null cache OpenAPI definitions */
    private ?array $definitions = null;

    /**
     * @param array{url: string, key: string, schema?: string, timeout?: int|float} $config
     */
    public function __construct(array $config, ?ClientInterface $client = null)
    {
        $url = rtrim(trim($config['url'] ?? ''), '/');
        $key = trim($config['key'] ?? '');

        if ($url === '' || $key === '') {
            throw new SupabaseException(
                'SupabaseCredentialMissing: entri DSN driver supabase butuh <url> dan <key>'
            );
        }

        $this->base = $url . '/rest/v1/';
        $this->key = $key;
        $this->schema = trim($config['schema'] ?? '') ?: 'public';
        $this->client = $client ?? new Client(['timeout' => (float) ($config['timeout'] ?? 15)]);
    }

    // -----------------------------------------------------------------
    // Operasi REST level-baris — dipakai SupabaseCrudRepository
    // -----------------------------------------------------------------

    /**
     * SELECT dengan filter equality (AND), pagination, dan order.
     *
     * @param array<string, mixed> $filter kolom => nilai (eq.)
     * @return array<int, array<string, mixed>>
     */
    public function restSelect(
        string $table,
        array $filter = [],
        int $limit = 0,
        int $offset = 0,
        string $orderBy = ''
    ): array {
        $query = $this->filterParams($filter);

        if ($orderBy !== '') {
            $query['order'] = $this->orderParam($orderBy);
        }

        if ($limit > 0) {
            $query['limit'] = (string) $limit;
            $query['offset'] = (string) max(0, $offset);
        }

        $rows = $this->decode($this->request('GET', $table, ['query' => $query]));

        return is_array($rows) ? $rows : [];
    }

    /**
     * INSERT satu baris; mengembalikan representasi baris hasil insert.
     *
     * @return array<string, mixed>|null
     */
    public function restInsert(string $table, array $data): ?array
    {
        $response = $this->request('POST', $table, [
            'json' => $data,
            'headers' => ['Prefer' => 'return=representation'],
        ]);

        $rows = $this->decode($response);
        $row = is_array($rows) ? ($rows[0] ?? null) : null;

        $this->lastAffected = is_array($rows) ? count($rows) : 0;
        $this->lastInsertId = (int) ($row['id'] ?? 0);

        return $row;
    }

    /**
     * UPDATE baris yang cocok filter. Filter kosong ditolak (guardrail:
     * PATCH tanpa filter di PostgREST menyentuh SEMUA baris).
     */
    public function restUpdate(string $table, array $data, array $filter): int
    {
        $this->requireFilter($filter, 'update');

        $response = $this->request('PATCH', $table, [
            'query' => $this->filterParams($filter),
            'json' => $data,
            'headers' => ['Prefer' => 'return=representation'],
        ]);

        $rows = $this->decode($response);

        return $this->lastAffected = is_array($rows) ? count($rows) : 0;
    }

    /**
     * DELETE baris yang cocok filter. Filter kosong ditolak.
     */
    public function restDelete(string $table, array $filter): int
    {
        $this->requireFilter($filter, 'delete');

        $response = $this->request('DELETE', $table, [
            'query' => $this->filterParams($filter),
            'headers' => ['Prefer' => 'return=representation'],
        ]);

        $rows = $this->decode($response);

        return $this->lastAffected = is_array($rows) ? count($rows) : 0;
    }

    /**
     * COUNT via header Prefer: count=exact (HEAD request, tanpa body).
     */
    public function restCount(string $table, array $filter = []): int
    {
        $response = $this->request('HEAD', $table, [
            'query' => $this->filterParams($filter),
            'headers' => ['Prefer' => 'count=exact'],
        ]);

        // Content-Range: "0-24/57" atau "*/0"
        $range = $response->getHeaderLine('Content-Range');
        $slash = strrpos($range, '/');

        return $slash === false ? 0 : (int) substr($range, $slash + 1);
    }

    // -----------------------------------------------------------------
    // DatabaseInterface
    // -----------------------------------------------------------------

    public function query(string $sql, mixed ...$params): array
    {
        throw $this->unsupported('query', $sql);
    }

    public function queryFirstRow(string $sql, mixed ...$params): ?array
    {
        throw $this->unsupported('queryFirstRow', $sql);
    }

    public function queryFirstColumn(string $sql, mixed ...$params): array
    {
        throw $this->unsupported('queryFirstColumn', $sql);
    }

    public function queryFirstField(string $sql, mixed ...$params): mixed
    {
        throw $this->unsupported('queryFirstField', $sql);
    }

    public function insert(string $table, array $data): int
    {
        $this->restInsert($table, $data);

        return $this->lastInsertId;
    }

    public function update(string $table, array $data, string $where, mixed ...$params): int
    {
        return $this->restUpdate($table, $data, $this->whereToFilter($where, $params, 'update'));
    }

    public function delete(string $table, string $where, mixed ...$params): int
    {
        return $this->restDelete($table, $this->whereToFilter($where, $params, 'delete'));
    }

    public function count(string $table, string $where = '', mixed ...$params): int
    {
        $filter = trim($where) === '' ? [] : $this->whereToFilter($where, $params, 'count');

        return $this->restCount($table, $filter);
    }

    public function startTransaction(): void
    {
        throw $this->unsupported('startTransaction');
    }

    public function commit(): void
    {
        throw $this->unsupported('commit');
    }

    public function rollback(): void
    {
        throw $this->unsupported('rollback');
    }

    public function insertId(): int
    {
        return $this->lastInsertId;
    }

    public function affectedRows(): int
    {
        return $this->lastAffected;
    }

    /**
     * Daftar kolom dari skema OpenAPI PostgREST (GET /rest/v1/), bentuk
     * mengikuti MeekroDB columnList(): nama kolom => metadata. Definisi
     * di-cache per instance (satu request per lifetime adapter).
     */
    public function columnList(string $table): array
    {
        if ($this->definitions === null) {
            $spec = $this->decode($this->request('GET', '', []));
            $this->definitions = is_array($spec) ? ($spec['definitions'] ?? []) : [];
        }

        $properties = $this->definitions[$table]['properties'] ?? null;

        if ($properties === null) {
            throw new SupabaseException(
                "SupabaseUnknownTable: tabel '{$table}' tidak ditemukan di schema {$this->schema}"
            );
        }

        $columns = [];

        foreach ($properties as $name => $meta) {
            $columns[$name] = ['type' => $meta['format'] ?? ($meta['type'] ?? '')];
        }

        return $columns;
    }

    // -----------------------------------------------------------------
    // Internal
    // -----------------------------------------------------------------

    private function request(string $method, string $path, array $options): ResponseInterface
    {
        $options['headers'] = ($options['headers'] ?? []) + [
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
            'Accept' => 'application/json',
        ];

        // Schema non-default: PostgREST pakai header profile, bukan path
        if ($this->schema !== 'public') {
            $profile = in_array($method, ['GET', 'HEAD'], true) ? 'Accept-Profile' : 'Content-Profile';
            $options['headers'][$profile] = $this->schema;
        }

        try {
            return $this->client->request($method, $this->base . $path, $options);
        } catch (BadResponseException $e) {
            throw $this->mapError($e);
        } catch (GuzzleException $e) {
            throw new SupabaseException('SupabaseUnreachable: ' . $e->getMessage(), 0, '', $e);
        }
    }

    /**
     * Respons error PostgREST → SupabaseException dengan status HTTP +
     * kode error PostgreSQL (body error PostgREST: message/code/hint).
     */
    private function mapError(BadResponseException $e): SupabaseException
    {
        $status = $e->getResponse()->getStatusCode();
        $body = json_decode((string) $e->getResponse()->getBody(), true);

        $message = is_array($body) ? ($body['message'] ?? $e->getMessage()) : $e->getMessage();
        $pgCode = is_array($body) ? (string) ($body['code'] ?? '') : '';
        $hint = is_array($body) ? (string) ($body['hint'] ?? '') : '';

        return new SupabaseException(
            "SupabaseError: HTTP {$status}"
                . ($pgCode !== '' ? " [{$pgCode}]" : '')
                . " {$message}"
                . ($hint !== '' ? " — hint: {$hint}" : ''),
            $status,
            $pgCode,
            $e
        );
    }

    private function decode(ResponseInterface $response): mixed
    {
        $body = (string) $response->getBody();

        return $body === '' ? null : json_decode($body, true);
    }

    /**
     * Filter kolom => nilai → query param PostgREST (eq. / is.null / is.bool).
     *
     * @param array<string, mixed> $filter
     * @return array<string, string>
     */
    private function filterParams(array $filter): array
    {
        $params = [];

        foreach ($filter as $column => $value) {
            $params[$column] = match (true) {
                $value === null => 'is.null',
                is_bool($value) => 'is.' . ($value ? 'true' : 'false'),
                default => 'eq.' . $value,
            };
        }

        return $params;
    }

    /**
     * "id DESC, nama" → "id.desc,nama.asc" (sintaks order PostgREST).
     */
    private function orderParam(string $orderBy): string
    {
        $parts = [];

        foreach (explode(',', $orderBy) as $piece) {
            $piece = trim($piece);

            if ($piece === '') {
                continue;
            }

            $segments = preg_split('/\s+/', $piece);
            $direction = strtolower($segments[1] ?? '') === 'desc' ? 'desc' : 'asc';
            $parts[] = $segments[0] . '.' . $direction;
        }

        return implode(',', $parts);
    }

    /**
     * Terjemahkan fragmen WHERE equality gaya MeekroDB ("id=%i",
     * "a=%i AND b=%s") menjadi filter kolom => nilai. Bentuk lain (OR,
     * operator non-=, placeholder selain %i/%s/%d, jumlah param tak
     * cocok) → UnsupportedDriverOperationException.
     *
     * @param array<int, mixed> $params
     * @return array<string, mixed>
     */
    private function whereToFilter(string $where, array $params, string $operation): array
    {
        $parts = preg_split('/\s+AND\s+/i', trim($where));
        $filter = [];

        foreach ($parts as $index => $part) {
            if (
                !preg_match('/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*%[isd]\s*$/', $part, $match)
                || !array_key_exists($index, $params)
            ) {
                throw $this->unsupported($operation, $where);
            }

            $filter[$match[1]] = $params[$index];
        }

        return $filter;
    }

    private function requireFilter(array $filter, string $operation): void
    {
        if (empty($filter)) {
            throw new SupabaseException(
                "SupabaseUnfilteredWrite: {$operation} tanpa filter akan menyentuh semua baris — ditolak"
            );
        }
    }

    private function unsupported(string $operation, string $detail = ''): UnsupportedDriverOperationException
    {
        return new UnsupportedDriverOperationException(
            "UnsupportedDriverOperation: {$operation} tidak tersedia di driver supabase"
                . ' — gunakan repo() (CrudRepositoryInterface)'
                . ($detail !== '' ? " [{$detail}]" : '')
        );
    }
}
