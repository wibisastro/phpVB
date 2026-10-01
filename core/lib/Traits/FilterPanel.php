<?php

namespace Gov2lib\Traits;

/**
 * Pola M9 — panel filter sidepanel.
 *
 * Dipakai berpasangan: controller memakai endpoint cmd (filterFields/filterApply/filterReset/filterResetAll),
 * model memakai filterWhere() untuk merakit WhereClause dari json/{mvc}_filter.json + sesi.
 * Blok "where" di JSON hanya dibaca server dan tidak pernah dikirim ke klien.
 * M9 sementara khusus driver MySQL/MeekroDB; cabang repo (Supabase) menolak filter aktif
 * lewat exceptionHandler alih-alih diam-diam mengabaikannya.
 *
 * FILTER GLOBAL LINTAS MVC (F3b): nilai disimpan PER ELEMEN, bukan per MVC — `$_SESSION['filter_global'][key]`.
 * `key` default = `name` elemen, sehingga elemen bernama sama di MVC mana pun memakai nilai yang sama
 * (sidepanel persisten lintas halaman seperti Wilayah/Instansi). Opt-out di JSON: `"scope":"mvc"` (kunci
 * di-namespace className) atau `"key":"…"` eksplisit. Elemen ber-`options` statis hanya menerima nilai di dalam
 * opsinya (nilai dari MVC lain yang tak cocok diabaikan, tak dihitung). Teks tampilan pilihan (untuk chip)
 * disimpan di `$_SESSION['filter_global_teks'][key]` — dikirim klien saat Terapkan, hanya untuk tampilan.
 * Argumen `$instance` pada metode publik dipertahankan demi kompatibilitas dan DIABAIKAN.
 *
 * @phpstan-ignore trait.unused
 */
trait FilterPanel
{
    /** @var array<int, array<string, mixed>>|null */
    protected ?array $filterPanelFieldsCache = null;

    /** GET /{app}/{mvc}/filterFields -> {fields, nilai, aktif, chips} (tanpa blok where). */
    public function filterFields(array $vars = []): mixed
    {
        global $self, $doc;

        return $doc->responseGet($self->filterFieldsData((string) ($_GET['instance'] ?? ($vars['instance'] ?? ''))));
    }

    /** POST {cmd:'filterApply', data:{nilai:{name: value}, teks:{name:[teks]}}} -> {class:'success', aktif:n, chips:[…]}. */
    public function filterApply(): array
    {
        global $self;

        $data = $_POST['data'] ?? [];
        $nilai = $data['nilai'] ?? [];
        $teks = $data['teks'] ?? [];
        $aktif = $self->filterApplyData(
            (string) ($data['instance'] ?? ''),
            is_array($nilai) ? $nilai : [],
            is_array($teks) ? $teks : []
        );

        return [
            'class' => 'success',
            'callback' => 'infoSnackbar',
            'notification' => 'Filter diterapkan',
            'aktif' => $aktif,
            'chips' => $self->filterPanelChips(),
        ];
    }

    /** POST {cmd:'filterReset'} — mengosongkan elemen yang tampil di MVC ini (elemen MVC lain tetap). */
    public function filterReset(): array
    {
        global $self;

        $self->filterResetData('');

        return [
            'class' => 'success',
            'callback' => 'infoSnackbar',
            'notification' => 'Filter direset',
            'aktif' => 0,
            'chips' => [],
        ];
    }

    /** POST {cmd:'filterResetAll'} — mengosongkan SEMUA filter (semua MVC) di sesi ini. */
    public function filterResetAll(): array
    {
        global $self;

        $self->filterResetAllData();

        return [
            'class' => 'success',
            'callback' => 'infoSnackbar',
            'notification' => 'Semua filter direset',
            'aktif' => 0,
            'chips' => [],
        ];
    }

    /** Data untuk endpoint filterFields: field tanpa "where" + nilai aktif (hanya elemen MVC ini) + chip. */
    public function filterFieldsData(string $instance = ''): array
    {
        $aktif = $this->filterPanelActive();

        $fields = array_map(function (array $field): array {
            unset($field['where']);
            return $field;
        }, $this->filterPanelFields());

        return [
            'fields' => array_values($fields),
            'nilai' => $aktif,
            'aktif' => count($aktif),
            'chips' => $this->filterPanelChips(),
        ];
    }

    /**
     * Simpan nilai filter ke sesi per ELEMEN. Elemen MVC ini yang tak ada/kosong di $nilai dikosongkan
     * (panel mengirim kondisi lengkapnya); elemen MVC lain tak tersentuh.
     *
     * @param array<string, mixed> $nilai {name: value}
     * @param array<string, mixed> $teks  {name: [teks tampilan]} — hanya untuk chip
     * @return int jumlah filter aktif di MVC ini
     */
    public function filterApplyData(string $instance, array $nilai, array $teks = []): int
    {
        foreach ($this->filterPanelFields() as $field) {
            $name = $field['name'];
            $key = $this->filterPanelKey($field);
            $value = array_key_exists($name, $nilai) ? $this->filterPanelValid($field, $nilai[$name]) : null;

            if ($value === null) {
                unset($_SESSION['filter_global'][$key], $_SESSION['filter_global_teks'][$key]);
                continue;
            }

            $_SESSION['filter_global'][$key] = $value;
            $bersih = $this->filterPanelTeksBersih($teks[$name] ?? null);
            if ($bersih) {
                $_SESSION['filter_global_teks'][$key] = $bersih;
            } else {
                unset($_SESSION['filter_global_teks'][$key]);
            }
        }

        return count($this->filterPanelActive());
    }

    /** Nilai filter aktif satu elemen MVC ini (untuk elemen tanpa blok "where" yang ditangani model sendiri). */
    public function filterValue(string $name, string $instance = ''): mixed
    {
        foreach ($this->filterPanelFields() as $field) {
            if ($field['name'] === $name) {
                return $this->filterPanelSesi($field);
            }
        }
        return null;
    }

    /** Kosongkan elemen yang tampil di MVC ini. */
    public function filterResetData(string $instance = ''): void
    {
        foreach ($this->filterPanelFields() as $field) {
            $key = $this->filterPanelKey($field);
            unset($_SESSION['filter_global'][$key], $_SESSION['filter_global_teks'][$key]);
        }
    }

    /** Kosongkan seluruh filter (semua MVC). */
    public function filterResetAllData(): void
    {
        unset($_SESSION['filter_global'], $_SESSION['filter_global_teks']);
    }

    /** Dipertahankan demi kompatibilitas: filter global tak lagi per instance. */
    protected function filterPanelInstance(): string
    {
        return '';
    }

    protected function filterPanelHasActive(string $instance = ''): bool
    {
        return count($this->filterPanelActive()) > 0;
    }

    /** Kunci sesi satu elemen: "key" eksplisit > scope=mvc (className.name) > name. */
    protected function filterPanelKey(array $field): string
    {
        $key = (string) ($field['key'] ?? '');
        if ($key === '') {
            $key = (($field['scope'] ?? 'global') === 'mvc')
                ? $this->filterPanelClassName() . '.' . $field['name']
                : (string) $field['name'];
        }
        if (!preg_match('/^[A-Za-z0-9_.\-]{1,80}$/', $key)) {
            throw new \InvalidArgumentException("FilterPanel: key tidak valid: {$key}");
        }
        return $key;
    }

    /**
     * Nilai valid untuk elemen ini (cast sesuai JSON; elemen ber-options statis hanya menerima id di dalam opsinya),
     * atau null bila kosong/tak cocok.
     */
    protected function filterPanelValid(array $field, mixed $value): mixed
    {
        if ($this->filterPanelEmpty($value)) {
            return null;
        }
        $value = $this->filterPanelCast($value, $this->filterPanelCastOf($field));

        if (isset($field['options']) && is_array($field['options']) && $field['options']) {
            $ids = array_map(fn ($o) => (string) (is_array($o) ? ($o['id'] ?? '') : $o), $field['options']);
            if (is_array($value)) {
                $value = array_values(array_filter($value, fn ($v) => in_array((string) $v, $ids, true)));
            } elseif (!in_array((string) $value, $ids, true)) {
                return null;
            }
        }

        return $this->filterPanelEmpty($value) ? null : $value;
    }

    /** Nilai sesi (valid) untuk satu elemen MVC ini. */
    protected function filterPanelSesi(array $field): mixed
    {
        $key = $this->filterPanelKey($field);
        if (!isset($_SESSION['filter_global']) || !is_array($_SESSION['filter_global']) || !array_key_exists($key, $_SESSION['filter_global'])) {
            return null;
        }
        return $this->filterPanelValid($field, $_SESSION['filter_global'][$key]);
    }

    /** @return array<string, mixed> {name: value} elemen MVC ini yang sedang aktif */
    protected function filterPanelActive(): array
    {
        $aktif = [];
        foreach ($this->filterPanelFields() as $field) {
            $v = $this->filterPanelSesi($field);
            if ($v !== null) {
                $aktif[$field['name']] = $v;
            }
        }
        return $aktif;
    }

    /**
     * Chip filter aktif: [{name, label, nilai, teks[]}]. Teks = teks tampilan tersimpan, atau teks opsi statis,
     * atau nilai mentah.
     *
     * @return array<int, array<string, mixed>>
     */
    public function filterPanelChips(): array
    {
        $chips = [];
        foreach ($this->filterPanelFields() as $field) {
            $v = $this->filterPanelSesi($field);
            if ($v === null) {
                continue;
            }
            $key = $this->filterPanelKey($field);
            $teks = $_SESSION['filter_global_teks'][$key] ?? null;
            if (!is_array($teks) || !$teks) {
                $peta = [];
                foreach ((array) ($field['options'] ?? []) as $o) {
                    if (is_array($o)) {
                        $peta[(string) ($o['id'] ?? '')] = (string) ($o['text'] ?? ($o['id'] ?? ''));
                    }
                }
                $teks = array_map(fn ($x) => $peta[(string) $x] ?? (string) $x, is_array($v) ? $v : [$v]);
            }
            $chips[] = [
                'name' => $field['name'],
                'label' => (string) ($field['label'] ?? $field['name']),
                'nilai' => $v,
                'teks' => array_values(array_map('strval', $teks)),
            ];
        }
        return $chips;
    }

    /** Teks tampilan dari klien: daftar string pendek saja. @return array<int, string> */
    protected function filterPanelTeksBersih(mixed $teks): array
    {
        if (!is_array($teks)) {
            return [];
        }
        $out = [];
        foreach (array_slice($teks, 0, 50) as $t) {
            if (is_scalar($t)) {
                $s = trim((string) $t);
                if ($s !== '') {
                    $out[] = mb_substr($s, 0, 200);
                }
            }
        }
        return $out;
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

        foreach ($this->filterPanelFields() as $field) {
            $name = $field['name'];
            $sesi = empty($field['where']) ? null : $this->filterPanelSesi($field);
            if ($sesi === null) {
                continue;
            }

            $kolom = $field['where']['kolom'] ?? '';
            $op = strtolower((string) ($field['where']['op'] ?? '='));
            // like dengan daftar kolom = cocok di salah satu kolom (mis. nama/kode/deskripsi)
            $columns = is_array($kolom) ? array_map([$this, 'filterPanelColumn'], $kolom) : [];
            $column = is_array($kolom) ? '' : $this->filterPanelColumn((string) $kolom);
            $cast = $this->filterPanelCastOf($field);
            $value = $sesi;

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
