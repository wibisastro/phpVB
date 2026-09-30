<?php

namespace Gov2lib\Traits;

/**
 * Pola M9 — panel filter sidepanel.
 *
 * Dipakai berpasangan: controller memakai endpoint cmd (filterFields/filterApply/filterReset),
 * model memakai filterWhere() untuk merakit WhereClause dari json/{mvc}_filter.json + sesi.
 * Blok "where" di JSON hanya dibaca server dan tidak pernah dikirim ke klien.
 * M9 sementara khusus driver MySQL/MeekroDB; cabang repo (Supabase) menolak filter aktif
 * lewat exceptionHandler alih-alih diam-diam mengabaikannya.
 *
 * @phpstan-ignore trait.unused
 */
trait FilterPanel
{
    /** @var array<int, array<string, mixed>>|null */
    protected ?array $filterPanelFieldsCache = null;

    /** GET /{app}/{mvc}/filterFields?instance=... -> {fields, nilai, aktif} (tanpa blok where). */
    public function filterFields(array $vars = []): mixed
    {
        global $self, $doc;

        $instance = (string) ($_GET['instance'] ?? ($vars['instance'] ?? ''));
        return $doc->responseGet($self->filterFieldsData($instance));
    }

    /** POST {cmd:'filterApply', data:{instance, nilai:{name: value}}} -> {class:'success', aktif:n}. */
    public function filterApply(): array
    {
        global $self;

        $data = $_POST['data'] ?? [];
        $nilai = $data['nilai'] ?? [];
        $aktif = $self->filterApplyData(
            (string) ($data['instance'] ?? ''),
            is_array($nilai) ? $nilai : []
        );

        return [
            'class' => 'success',
            'callback' => 'infoSnackbar',
            'notification' => 'Filter diterapkan',
            'aktif' => $aktif,
        ];
    }

    /** POST {cmd:'filterReset', data:{instance}} -> {class:'success', aktif:0}. */
    public function filterReset(): array
    {
        global $self;

        $data = $_POST['data'] ?? [];
        $self->filterResetData((string) ($data['instance'] ?? ''));

        return [
            'class' => 'success',
            'callback' => 'infoSnackbar',
            'notification' => 'Filter direset',
            'aktif' => 0,
        ];
    }

    /** Data untuk endpoint filterFields: field tanpa "where" + nilai aktif dari sesi. */
    public function filterFieldsData(string $instance = ''): array
    {
        $nilai = $_SESSION['filter'][$this->filterPanelSessionKey($instance)] ?? [];

        $fields = array_map(function (array $field): array {
            unset($field['where']);
            return $field;
        }, $this->filterPanelFields());

        return [
            'fields' => array_values($fields),
            'nilai' => $nilai,
            'aktif' => count($nilai),
        ];
    }

    /** Simpan nilai filter tervalidasi ke sesi ber-namespace per MVC+instance. @return int jumlah filter aktif */
    public function filterApplyData(string $instance, array $nilai): int
    {
        $known = [];
        foreach ($this->filterPanelFields() as $field) {
            $known[$field['name']] = $field;
        }

        $clean = [];
        foreach ($nilai as $name => $value) {
            if (!isset($known[$name]) || $this->filterPanelEmpty($value)) {
                continue;
            }
            $clean[$name] = $this->filterPanelCast($value, $this->filterPanelCastOf($known[$name]));
        }

        $_SESSION['filter'][$this->filterPanelSessionKey($instance)] = $clean;
        return count($clean);
    }

    /** Nilai filter aktif satu field (untuk field tanpa blok "where" yang ditangani model sendiri). */
    public function filterValue(string $name, string $instance = ''): mixed
    {
        return $_SESSION['filter'][$this->filterPanelSessionKey($instance)][$name] ?? null;
    }

    public function filterResetData(string $instance = ''): void
    {
        unset($_SESSION['filter'][$this->filterPanelSessionKey($instance)]);
    }

    /** Instance tabel untuk namespace sesi; MVC multi-instance (M3) boleh override. */
    protected function filterPanelInstance(): string
    {
        return '';
    }

    protected function filterPanelHasActive(string $instance = ''): bool
    {
        return !empty($_SESSION['filter'][$this->filterPanelSessionKey($instance)]);
    }

    /**
     * Rakit WhereClause dari JSON + sesi. Kolom hanya dari JSON (divalidasi), nilai selalu
     * lewat placeholder MeekroDB — tidak ada interpolasi mentah.
     *
     * @return \WhereClause
     */
    public function filterWhere(string $instance = '')
    {
        $where = $this->filterPanelWhereClause('and');
        $nilai = $_SESSION['filter'][$this->filterPanelSessionKey($instance)] ?? [];

        foreach ($this->filterPanelFields() as $field) {
            $name = $field['name'];
            if (empty($field['where']) || !array_key_exists($name, $nilai) || $this->filterPanelEmpty($nilai[$name])) {
                continue;
            }

            $kolom = $field['where']['kolom'] ?? '';
            $op = strtolower((string) ($field['where']['op'] ?? '='));
            // like dengan daftar kolom = cocok di salah satu kolom (mis. nama/kode/deskripsi)
            $columns = is_array($kolom) ? array_map([$this, 'filterPanelColumn'], $kolom) : [];
            $column = is_array($kolom) ? '' : $this->filterPanelColumn((string) $kolom);
            $cast = $this->filterPanelCastOf($field);
            $value = $this->filterPanelCast($nilai[$name], $cast);

            if (!in_array($op, ['=', 'like', 'in', 'find_in_set', '>=', '<='], true)) {
                throw new \InvalidArgumentException("FilterPanel: op tidak dikenal untuk {$name}: {$op}");
            }

            if ($columns && $op !== 'like') {
                throw new \InvalidArgumentException("FilterPanel: kolom berupa daftar hanya untuk op like ({$name})");
            }

            if ($op === 'like') {
                $term = (string) (is_array($value) ? reset($value) : $value);
                if ($columns) {
                    $or = $this->filterPanelWhereClause('or');
                    $where->add($or);
                    foreach ($columns as $col) {
                        $or->add("{$col} LIKE %ss", $term);
                    }
                    continue;
                }
                $where->add("{$column} LIKE %ss", $term);
                continue;
            }

            if ($op === 'in') {
                $values = array_values(array_filter(
                    is_array($value) ? $value : [$value],
                    fn ($v) => !$this->filterPanelEmpty($v)
                ));
                if (!$values) {
                    continue;
                }
                $where->add("{$column} IN " . ($cast === 'int' ? '%li' : '%ls'), $values);
                continue;
            }

            if ($op === 'find_in_set') {
                $values = array_values(array_filter(
                    is_array($value) ? $value : [$value],
                    fn ($v) => !$this->filterPanelEmpty($v)
                ));
                if (!$values) {
                    continue;
                }
                $or = $this->filterPanelWhereClause('or');
                $where->add($or);
                foreach ($values as $item) {
                    $or->add("FIND_IN_SET(%s, {$column})", (string) $item);
                }
                continue;
            }

            if (is_array($value)) {
                $or = $this->filterPanelWhereClause('or');
                $where->add($or);
                foreach ($value as $item) {
                    if ($this->filterPanelEmpty($item)) {
                        continue;
                    }
                    $or->add("{$column} {$op} " . ($cast === 'int' ? '%i' : '%s'), $item);
                }
                continue;
            }

            $where->add("{$column} {$op} " . ($cast === 'int' ? '%i' : '%s'), $value);
        }

        return $where;
    }

    /** Factory kecil supaya WhereClause bisa dipalsukan di unit test tanpa ekstensi mysqli. */
    protected function filterPanelWhereClause(string $type): object
    {
        return new \WhereClause($type);
    }

    /** @return array<int, array<string, mixed>> */
    protected function filterPanelFields(): array
    {
        if ($this->filterPanelFieldsCache !== null) {
            return $this->filterPanelFieldsCache;
        }

        $path = $this->filterPanelPath();
        $fields = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];

        $this->filterPanelFieldsCache = [];
        foreach (is_array($fields) ? $fields : [] as $field) {
            if (!is_array($field) || empty($field['name'])) {
                continue;
            }
            $this->filterPanelFieldsCache[] = $field;
        }

        return $this->filterPanelFieldsCache;
    }

    protected function filterPanelPath(): string
    {
        $class = $this->filterPanelClassName();
        $dir = dirname((new \ReflectionClass($this))->getFileName());

        foreach ([$dir . '/../json/' . $class . '_filter.json', $dir . '/json/' . $class . '_filter.json'] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return $dir . '/../json/' . $class . '_filter.json';
    }

    protected function filterPanelClassName(): string
    {
        if (!empty($this->className)) {
            return (string) $this->className;
        }
        return basename((new \ReflectionClass($this))->getFileName(), '.php');
    }

    protected function filterPanelSessionKey(string $instance): string
    {
        return $this->filterPanelClassName() . $instance;
    }

    protected function filterPanelColumn(string $column): string
    {
        if (!preg_match('/^[a-z_][a-z0-9_]*(\.[a-z_][a-z0-9_]*)?$/i', $column)) {
            throw new \InvalidArgumentException("FilterPanel: kolom tidak valid: {$column}");
        }
        return $column;
    }

    protected function filterPanelCastOf(array $field): string
    {
        return (($field['where']['cast'] ?? 'str') === 'int') ? 'int' : 'str';
    }

    protected function filterPanelCast(mixed $value, string $cast): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->filterPanelCast($item, $cast), $value);
        }
        return $cast === 'int' ? (int) $value : trim((string) $value);
    }

    protected function filterPanelEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && count($value) === 0);
    }
}
