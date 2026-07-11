<?php

namespace Gov2lib;

/**
 * Data model implementing CRUD operations with hierarchical data support.
 *
 * @version 2.0 - PHP 8.4 refactor
 */
class crudModel extends dsnSource
{
    /** @var array<int, array<string, mixed>> Breadcrumb data */
    protected array $breadcrumb = [];

    public function __construct(string $dsn = '')
    {
        parent::__construct();
        $this->connectDB($dsn);
    }

    /**
     * Driver-branch (fase T4 #6085): true bila DSN aktif memakai driver
     * non-SQL (supabase) sehingga method CRUD harus lewat repo() (REST).
     * Jalur meekro tetap menjalankan SQL existing byte-identik — ganti
     * tier cukup dengan mengubah <driver> di DSN XML, tanpa override model.
     */
    protected function usesRepo(): bool
    {
        return $this->repo() instanceof Database\SupabaseCrudRepository;
    }

    /**
     * Guard operasi join-heavy yang deliberately meekro-only (keputusan
     * T4: defer — belum ada pemetaan JOIN/alias kolom ke PostgREST).
     */
    private function assertSqlDriver(string $operation): void
    {
        if ($this->usesRepo()) {
            throw new Exceptions\UnsupportedDriverOperationException(
                "UnsupportedDriverOperation: {$operation} butuh JOIN/alias kolom SQL"
                    . ' yang belum terpetakan ke PostgREST — sediakan RPC/view di'
                    . ' Supabase bila operasi ini diperlukan di driver supabase'
            );
        }
    }

    /**
     * Browse tags linked to a source entity.
     *
     * @return array<int, array<string, mixed>>|null
     */
    public function doBrowseTags(
        int $sourceId,
        string $source,
        string $target,
        string $target2 = '',
        string $caption = ''
    ): ?array {
        $this->assertSqlDriver('doBrowseTags');

        try {
            $query = "SELECT *,
                {$target}_{$caption} AS target_{$caption},
                {$target}_id AS target_id,
                {$source}_id AS source_id";

            if ($target2) {
                $query .= ",{$target2}_id AS target2_id ";
            }

            $query .= " FROM " . $this->tbl->table . " WHERE {$source}_parent=%i";

            if ($target2) {
                $query .= " AND {$target2}_id=%i";
            }

            return $this->db()->query($query, $sourceId, $this->ses->val[$target2 . '_id'] ?? 0);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler("doBrowseTags:{$e->getMessage()}");
            return null;
        }
    }

    /**
     * Create a tag linking source to target entities.
     */
    public function doTagging(
        array $data,
        string $source,
        string $target,
        string $target2,
        string $caption
    ): ?int {
        $this->assertSqlDriver('doTagging');

        try {
            // Check if already tagged
            if ($target2) {
                $query = "SELECT * FROM " . $this->tbl->table
                    . " WHERE {$source}_id=%i AND {$target}_id=%i AND {$target2}_id=%i";
                $tagged = $this->db()->queryFirstRow(
                    $query,
                    $data['source_id'],
                    $data['target_id'],
                    $this->ses->val[$target2 . '_id'] ?? 0
                );
            } else {
                $query = "SELECT * FROM " . $this->tbl->table
                    . " WHERE {$source}_id=%i AND {$target}_id=%i";
                $tagged = $this->db()->queryFirstRow($query, $data['source_id'], $data['target_id']);
            }

            if (!empty($tagged['id'])) {
                throw new \Exception("AlreadyTagged: {$tagged[$target . '_' . $caption]} source_id={$data['source_id']}");
            }

            // Get source and target data
            $sourceData = $this->db()->queryFirstRow("SELECT * FROM " . $this->tbl->source . " WHERE id=%i", $data['source_id']);
            $targetData = $this->db()->queryFirstRow("SELECT * FROM " . $this->tbl->target . " WHERE id=%i", $data['target_id']);

            $insert = [
                "{$source}_id" => (int) $data['source_id'],
                "{$source}_parent" => $sourceData['parent_id'] ?? 0,
                "{$source}_nama" => $sourceData['nama'] ?? '',
                "{$source}_level" => (int) ($sourceData['level_label'] ?? 0),
                "{$target}_id" => (int) $data['target_id'],
                "{$target}_{$caption}" => $targetData[$caption] ?? '',
            ];

            // Handle target2 data
            if ($target2) {
                $target2Data = $this->db()->queryFirstRow(
                    "SELECT * FROM " . $this->tbl->{$target2} . " WHERE id=%i",
                    $this->ses->val[$target2 . '_id'] ?? 0
                );
            }

            // Check columns and add optional fields
            $columns = $this->db()->columnList($this->tbl->table);

            if (in_array("{$target}_level", $columns)) {
                $insert["{$target}_level"] = $targetData['level_label'] ?? '';
            }
            if ($target2 && in_array("{$target2}_id", $columns)) {
                $insert["{$target2}_id"] = $this->ses->val[$target2 . '_id'] ?? 0;
            }
            if ($target2 && in_array("{$target2}_{$caption}", $columns) && isset($target2Data)) {
                $insert["{$target2}_{$caption}"] = $target2Data[$caption] ?? '';
            }
            if ($target2 && in_array("{$target2}_level", $columns) && isset($target2Data)) {
                $insert["{$target2}_level"] = $target2Data['level_label'] ?? '';
            }
            if ($target2 && in_array("{$target2}_parent", $columns) && isset($target2Data)) {
                $insert["{$target2}_parent"] = $target2Data['parent_id'] ?? 0;
            }

            // Handle wilayah hierarchy
            if ($source === 'wilayah') {
                $insert = $this->resolveWilayahHierarchy($insert, (int) $data['source_id']);
            }

            return $this->db()->insert($this->tbl->table, $insert);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler("doTagging:{$e->getMessage()}");
            return null;
        } catch (\Exception $e) {
            $this->exceptionHandler($e->getMessage());
            return null;
        }
    }

    /**
     * Resolve wilayah geographic hierarchy for tagging.
     */
    private function resolveWilayahHierarchy(array $insert, int $sourceId): array
    {
        $this->assertSqlDriver('resolveWilayahHierarchy');

        $wilayah = $this->db()->queryFirstRow(
            "SELECT * FROM " . $this->tbl->wilayah . " WHERE id=%i",
            $sourceId
        );

        if (!$wilayah) {
            return $insert;
        }

        if ($wilayah['level'] == '2') {
            $insert['provinsi_id'] = $wilayah['parent_id'];
            $insert['kabupaten_id'] = $wilayah['id'];
            return $insert;
        }

        $hierarchyQuery = match ($wilayah['level']) {
            '3' => "SELECT 0 AS kel_id, kec.id AS kec_id,
                    kab.id AS kab_id, kab.parent_id AS prov_id
                    FROM " . $this->tbl->wilayah . " AS kec
                    LEFT JOIN " . $this->tbl->wilayah . " AS kab ON kec.parent_id=kab.id
                    WHERE kec.id=%i",
            '4' => "SELECT kel.id AS kel_id,
                    kec.id AS kec_id, kab.id AS kab_id,
                    kab.parent_id AS prov_id
                    FROM " . $this->tbl->wilayah . " AS kel
                    LEFT JOIN " . $this->tbl->wilayah . " AS kec ON kel.parent_id=kec.id
                    LEFT JOIN " . $this->tbl->wilayah . " AS kab ON kec.parent_id=kab.id
                    WHERE kel.id=%i",
            default => null,
        };

        if ($hierarchyQuery) {
            try {
                $resolved = $this->db()->queryFirstRow($hierarchyQuery, $wilayah['id']);
                $insert['provinsi_id'] = (int) ($resolved['prov_id'] ?? 0);
                $insert['kabupaten_id'] = (int) ($resolved['kab_id'] ?? 0);
                $insert['kecamatan_id'] = (int) ($resolved['kec_id'] ?? 0);
                $insert['kelurahan_id'] = (int) ($resolved['kel_id'] ?? 0);
            } catch (\MeekroDBException $e) {
                $this->exceptionHandler($e->getMessage());
            }
        }

        return $insert;
    }

    /**
     * Build a breadcrumb trail from a hierarchical record.
     */
    public function setBreadcrumb(int $id = 0, string $caption = '', string $code = ''): void
    {
        static $counter = 0;

        if ($this->usesRepo()) {
            try {
                $row = $this->repo()->read((string) $this->tbl->table, $id);

                if ($row === null) {
                    return;
                }

                $counter++;
                $this->breadcrumb[$counter] = [
                    'caption' => $caption ? ($row[$caption] ?? '') : ($row['nama'] ?? ''),
                    'id' => $row['id'],
                    'level' => $row['level'] ?? '',
                    'level_label' => $row['level_label'] ?? '',
                ];

                if ($code) {
                    $this->breadcrumb[$counter]['code'] = $row[$code] ?? '';
                }

                if (!empty($row['parent_id']) && $row['parent_id'] > 0) {
                    $this->setBreadcrumb((int) $row['parent_id'], $caption, $code);
                }
            } catch (Exceptions\SupabaseException $e) {
                $this->exceptionHandler($e->getMessage());
            }

            return;
        }

        try {
            $query = "SELECT * FROM " . $this->tbl->table . " WHERE id=%i";
            $results = $this->db()->query($query, $id);

            if (!is_array($results)) {
                return;
            }

            foreach ($results as $row) {
                $counter++;
                $this->breadcrumb[$counter] = [
                    'caption' => $caption ? ($row[$caption] ?? '') : ($row['nama'] ?? ''),
                    'id' => $row['id'],
                    'level' => $row['level'] ?? '',
                    'level_label' => $row['level_label'] ?? '',
                ];

                if ($code) {
                    $this->breadcrumb[$counter]['code'] = $row[$code] ?? '';
                }

                if (!empty($row['parent_id']) && $row['parent_id'] > 0) {
                    $this->setBreadcrumb((int) $row['parent_id'], $caption, $code);
                }
            }
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }
    }

    /**
     * Update a record with optional hierarchical data support.
     */
    public function doUpdate(array $data): void
    {
        unset($data['cmd']);
        $fields = $data;
        $columns = $this->db()->columnList($this->tbl->table);

        if (in_array('parent_id', $columns)) {
            $recursive = [
                'parent_id' => (int) ($data['parent_id'] ?? 0),
                'level_label' => $data['level_label'] ?? '',
                'level' => $data['level'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
            ];
            $fields = array_merge($fields, $recursive);
        } else {
            unset($fields['parent_id']);
        }

        try {
            $this->db()->update($this->tbl->table, $fields, "id=%i", (int) ($data['id'] ?? 0));

            if (!empty($data['id']) && in_array('parent_id', $columns)) {
                $this->updateChildren((int) $data['id']);
            }
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }
    }

    /**
     * Delete a record and update parent's child count.
     */
    public function doDel(int $id = 0): void
    {
        if ($this->usesRepo()) {
            try {
                $data = $this->doRead($id);
                $this->repo()->delete((string) $this->tbl->table, $id);

                if (!empty($data['parent_id'])) {
                    $this->updateChildren((int) $data['parent_id']);
                }
            } catch (Exceptions\SupabaseException $e) {
                $this->exceptionHandler($e->getMessage());
            }

            return;
        }

        try {
            $data = $this->doRead($id);
            $this->db()->delete($this->tbl->table, "id=%i", $id);

            if (!empty($data['parent_id'])) {
                $this->updateChildren((int) $data['parent_id']);
            }
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }
    }

    /**
     * Update the children count of a parent record.
     */
    public function updateChildren(int $id): void
    {
        $children = $this->doCountChildren($id);
        $fields = ['children' => $children['totalRecord'] ?? 0];

        if ($this->usesRepo()) {
            try {
                $this->repo()->update((string) $this->tbl->table, $fields, $id);
            } catch (Exceptions\SupabaseException $e) {
                $this->exceptionHandler($e->getMessage());
            }

            return;
        }

        try {
            $this->db()->update($this->tbl->table, $fields, "id=%i", $id);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
        }
    }

    /**
     * Add a new record with optional hierarchical data support.
     */
    public function doAdd(array $data): ?int
    {
        $accountId = isset($this->ses->val['account_id'])
            ? trim((string) $this->ses->val['account_id'])
            : '';

        unset($data['cmd']);
        $fields = $data;

        try {
            $columns = array_keys($this->db()->columnList($this->tbl->table));

            if (in_array('parent_id', $columns)) {
                $levelLabel = $this->gov2formfield->getLevel(
                    $this->fields,
                    $data['level'] ?? '',
                    $data['level_label'] ?? ''
                );
                $parent = $this->doRead((int) ($data['parent_id'] ?? 0));

                for ($i = ($parent['level'] ?? 0); $i >= 1; $i--) {
                    if ($i == ($parent['level'] ?? 0)) {
                        $fields[($parent['level_label'] ?? '') . '_id'] = $parent['id'] ?? 0;
                    } else {
                        $grandparent = $this->doRead((int) ($parent['parent_id'] ?? 0));
                        $parentLabel = $this->gov2formfield->getLevel(
                            $this->fields,
                            $i,
                            $grandparent['level_label'] ?? ''
                        );
                        $fields[$parentLabel . '_id'] = $parent[$parentLabel . '_id'] ?? 0;
                        $parent = $grandparent;
                    }
                }

                $recursive = [
                    'parent_id' => (int) ($data['parent_id'] ?? 0),
                    'level_label' => $levelLabel,
                    'level' => $data['level'] ?? '',
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $fields = array_merge($fields, $recursive);
            } else {
                unset($fields['parent_id']);
                $flat = [
                    'created_by' => is_numeric($accountId) ? (int) $accountId : 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $fields = array_merge($fields, $flat);
            }

            $id = $this->db()->insert($this->tbl->table, $fields);

            if (!empty($data['parent_id']) && in_array('parent_id', $columns)) {
                $this->updateChildren((int) $data['parent_id']);
            }

            return $id;
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
            return null;
        } catch (\Exception $e) {
            $this->exceptionHandler($e->getMessage());
            return null;
        }
    }

    /**
     * Read a single record by ID.
     *
     * @return array<string, mixed>|null
     */
    public function doRead(int $id = 0): ?array
    {
        global $doc;

        if ($this->usesRepo()) {
            try {
                return $this->repo()->read((string) $this->tbl->table, $id);
            } catch (Exceptions\SupabaseException $e) {
                $doc->exceptionHandler($e->getMessage());
                return null;
            }
        }

        $query = "SELECT * FROM " . $this->tbl->table . " WHERE id=%i";

        try {
            return $this->db()->queryFirstRow($query, $id);
        } catch (\MeekroDBException $e) {
            $doc->exceptionHandler($e->getMessage());
            return null;
        }
    }

    /**
     * Count children of a parent record.
     *
     * @return array{totalRecord: int}|null
     */
    public function doCountChildren(int|string $parentId = 0): ?array
    {
        if ($this->usesRepo()) {
            $filter = $parentId ? ['parent_id' => (int) $parentId] : [];

            return ['totalRecord' => $this->repo()->count((string) $this->tbl->table, $filter)];
        }

        $where = $parentId ? 'WHERE parent_id=%i' : '';
        $query = "SELECT count(id) as totalRecord FROM " . $this->tbl->table . " {$where}";

        return $this->db()->queryFirstRow($query, $parentId);
    }

    /**
     * Browse records with pagination.
     *
     * @return array<int, array<string, mixed>>|null
     */
    public function doBrowse(int|string $scroll = 0, int|string $parentId = 0, string $parentIdName = ''): ?array
    {
        if ($this->usesRepo()) {
            try {
                // Terjemahan scroll() "offset,interval" → limit/offset PostgREST;
                // order eksplisit by id = padanan praktis urutan PK MySQL
                $interval = $this->scrollInterval ?: 1000;
                $offset = max(0, (int) $scroll - 1) * $interval;
                $parentCol = $parentIdName ? "{$parentIdName}_id" : 'parent_id';
                $filter = $parentId ? [$parentCol => (int) $parentId] : [];

                return $this->repo()->browse((string) $this->tbl->table, $filter, $interval, $offset, 'id');
            } catch (Exceptions\SupabaseException $e) {
                $this->exceptionHandler($e->getMessage());
                return null;
            }
        }

        try {
            $scrolled = $this->scroll((int) $scroll);
            $parentCol = $parentIdName ? "{$parentIdName}_id" : 'parent_id';
            $where = $parentId ? "WHERE {$parentCol}=%i" : '';

            $query = "SELECT * FROM " . $this->tbl->table . " {$where} LIMIT {$scrolled}";

            return $this->db()->query($query, $parentId);
        } catch (\Exception $e) {
            $this->exceptionHandler($e->getMessage());
            return null;
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
            return null;
        }
    }
}
