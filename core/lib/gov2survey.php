<?php

namespace Gov2lib;

use DB;
use Exception;
use MeekroDBException;
use WhereClause;

/**
 * Survey API for managing survey data
 *
 * @package Gov2lib
 */
class gov2survey
{
    public string $table = 'survey_kuesioner_local';
    public mixed $dsn = null;

    /**
     * Initialize survey handler with database connection
     */
    public function __construct()
    {
        global $doc, $config;
        try {
            $cookies = $doc->envRead($_COOKIE['Gov2Session']);
            $this->dsn = $cookies['portal'];
            if (!$this->dsn) {
                $this->dsn = $config->domain->attr['dsn'];
            }
        } catch (Exception $e) {
            $this->dsn = $config->domain->attr['dsn'];
        }
    }

    /**
     * Get survey list by parent and level
     *
     * @param int $parent_id Parent record ID
     * @param string $level_label Level label (survey, pertanyaan, opsi)
     * @return array|object
     */
    final public function get_list(int $parent_id = 0, string $level_label = 'survey'): array|object
    {
        global $uri, $doc;
        $result = [];

        $connector = new DBConnector($this->dsn);

        $q = "SELECT * FROM {$this->table}
            WHERE parent_id=%i AND level_label=%s";

        try {
            $data = $connector->db->query($q, $parent_id, $level_label);
            $result = $this->collection($data, $level_label);
        } catch (MeekroDBException $e) {
            $doc->exceptionHandler($e->getMessage());
        } catch (Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }

        return $result;
    }

    /**
     * Get a single survey row
     *
     * @param array $where WHERE clause conditions
     * @param string $whereType AND or OR for WHERE clause
     * @param string[] $select Fields to select
     * @return null|array
     */
    final public function get(array $where = [], string $whereType = 'and', array $select = ['*']): ?array
    {
        global $doc;
        $select_field = join(',', $select);
        $where_clause = new WhereClause($whereType);
        $connector = new DBConnector($this->dsn);

        foreach ($where as $key => $val) {
            $kwarg = gettype($val) === 'string' ? "{$key}=%s" : "{$key}=%i";
            $where_clause->add($kwarg, $val);
        }

        $q = "SELECT {$select_field} FROM {$this->table} WHERE %l";
        $res = null;

        try {
            $res = $connector->db->queryFirstRow($q, $where_clause);
        } catch (MeekroDBException $e) {
            $doc->exceptionHandler($e->getMessage());
        } catch (Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }

        return $res;
    }

    /**
     * Create new survey record
     *
     * @param array $data Survey data
     * @return void
     */
    final public function create(array &$data): void
    {
        global $doc;
        $connector = new DBConnector($this->dsn);

        try {
            $connector->db->insert($this->table, $data);
            $data = $this->get(['id' => $connector->db->insertId()]);
        } catch (MeekroDBException $e) {
            $doc->exceptionHandler($e->getMessage());
        } catch (Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }
    }

    /**
     * Update survey record
     *
     * @param array $data Survey data with ID
     * @return void
     */
    final public function update(array &$data): void
    {
        global $doc;
        $connector = new DBConnector($this->dsn);

        try {
            $connector->db->update($this->table, $data, 'id=%i', $data['id']);
        } catch (MeekroDBException $e) {
            $doc->exceptionHandler($e->getMessage());
        } catch (Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }
    }

    /**
     * Get collection instance based on level label
     *
     * @param array $data Survey data array
     * @param string $level_label Level label
     * @return object
     */
    private function collection(array &$data, string $level_label): object
    {
        return match($level_label) {
            'survey' => new SurveyCollection($data),
            'pertanyaan' => new SurveyPertanyaanCollection($data),
            'opsi' => new SurveyOpsiCollection($data),
            default => new BaseSurveyCollection(),
        };
    }
}

/**
 * Base survey collection class
 */
class BaseSurveyCollection
{
    protected array $_items = [];

    /**
     * Get all items
     */
    public function items(): array
    {
        return $this->_items;
    }

    /**
     * Get single item by key or filter
     */
    public function get(int|array $key): ?object
    {
        $result = null;

        if (is_string($key)) {
            throw new Exception("Invalid key $key");
        }

        if (!is_array($key)) {
            $result = $this->_items[$key] ?? null;
        } else {
            $filtered = array_filter($this->_items, function($item) use($key) {
                $keys = array_keys($key);
                $true = [];

                foreach ($keys as $k) {
                    if ($item->{$k} == $key[$k]) {
                        array_push($true, 1);
                    }
                }

                if (count($keys) == array_sum($true)) {
                    return $item;
                }

                return false;
            });

            if ($filtered) {
                $result = $filtered[0] ?? null;
            }
        }

        return $result;
    }

    /**
     * Filter items by conditions
     */
    public function filter(array $key): array
    {
        $result = [];

        if (is_string($key) || !is_array($key)) {
            throw new Exception("Invalid key");
        }

        $result = array_filter($this->_items, function($item) use($key) {
            $keys = array_keys($key);
            $true = [];

            foreach ($keys as $k) {
                if ($item->{$k} == $key[$k]) {
                    array_push($true, 1);
                }
            }

            return array_sum($true) > 0;
        });

        return $result;
    }

    /**
     * Serialize all items
     */
    public function serialize(): array
    {
        $items = [];

        foreach($this->_items as $i => $item) {
            if (is_object($item) && method_exists($item, 'serialize')) {
                $items[$i] = $item->serialize();
            }
        }

        return $items;
    }

    /**
     * Get number of items
     */
    public function length(): int
    {
        return count($this->_items);
    }

    /**
     * Get all item keys
     */
    public function keys(): array
    {
        return array_keys($this->_items);
    }
}

/**
 * Base survey class
 */
class BaseSurvey
{
    /**
     * Serialize object to array
     */
    public function serialize(): array
    {
        return (array)$this;
    }

    /**
     * Save survey to database
     */
    public function save(): void
    {
        global $self;

        $this->created_by = $self->ses->val['account_id'];

        if ($this->id ?? false) {
            $this->modify_by = $self->ses->val['account_id'];
        }

        unset($this->created_at);
        unset($this->modify_at);

        $this->validate();

        $data = $this->serialize();

        if (isset($data['id']) && intval($data['id'])) {
            $self->sur->update($data);
        } else {
            $data = $self->sur->create($data);
            match($data['level_label']) {
                'survey' => $this->assignData(new SurveyEntity($data)),
                'pertanyaan' => $this->assignData(new SurveyPertanyaan($data)),
                'opsi' => $this->assignData(new SurveyOpsi($data)),
                default => null,
            };
        }
    }

    /**
     * Assign data properties
     */
    private function assignData(object $dataObj): void
    {
        foreach($dataObj as $key => $val) {
            $this->{$key} = $val;
        }
    }

    /**
     * Get object keys
     */
    public function keys(): array
    {
        return array_keys($this->serialize());
    }

    /**
     * Validate level based on parent
     */
    private function validate_level(): void
    {
        global $self;

        $parent_id = intval($this->parent_id ?? 0);

        if ($parent_id) {
            $parent = $self->sur->get(['id' => $parent_id]);
        } else {
            $parent = ['level_label' => 'root'];
        }

        match($parent['level_label'] ?? 'root') {
            'root' => [
                $this->level = 1,
                $this->level_label = 'survey',
            ],
            'survey' => [
                $this->level = 2,
                $this->level_label = 'pertanyaan',
            ],
            'pertanyaan' => [
                $this->level = 3,
                $this->level_label = 'opsi',
            ],
            default => null,
        };
    }

    /**
     * Validate survey data
     */
    private function validate(): void
    {
        if (!isset($this->nama) || !$this->nama) {
            throw new \Exception("Property 'nama' should not be empty");
        }

        if (!isset($this->app) || !$this->app) {
            throw new \Exception("Property 'app' should not be empty");
        }

        if (!isset($this->level) || !$this->level) {
            throw new \Exception("Property 'level' should not be empty");
        }

        if (!isset($this->level_label) || !$this->level_label) {
            throw new \Exception("Property 'level_label' should not be empty");
        }

        if (!isset($this->unit_id) || !$this->unit_id) {
            throw new \Exception("Property 'level_label' should not be empty");
        }

        $this->validate_level();

        match($this->level_label) {
            'survey' => $this->parent_id = 0,
            'pertanyaan' => [
                (!isset($this->parent_id) || intval($this->parent_id) == 0) && throw new \Exception("Property 'parent_id' should not be empty or 0"),
                (!isset($this->nomor) || !$this->nomor) && throw new \Exception("Property 'nomor' should not be empty or 0"),
            ],
            'opsi' => [
                (!isset($this->parent_id) || intval($this->parent_id) == 0) && throw new \Exception("Property 'parent_id' should not be empty or 0"),
                (!isset($this->nomor) || !$this->nomor) && throw new \Exception("Property 'nomor' should not be empty or 0"),
                (!isset($this->bobot) || !$this->bobot) && throw new \Exception("Property 'bobot' should not be empty or 0"),
            ],
            default => null,
        };
    }

    /**
     * Handle dynamic method calls for collections
     */
    public function __call(string $name, array $args): ?object
    {
        global $self;

        if (!isset($this->id) || !intval($this->id)) {
            return null;
        }

        return match ($name) {
            'pertanyaan' => ($this->level == 1) ? $self->sur->get_list($this->id, 'pertanyaan') : null,
            'opsi' => ($this->level == 2) ? $self->sur->get_list($this->id, 'opsi') : null,
            default => null,
        };
    }
}

/**
 * Survey collection
 */
class SurveyCollection extends BaseSurveyCollection
{
    /**
     * Initialize collection from array
     */
    public function __construct(array $survey_list)
    {
        foreach($survey_list as $survey) {
            array_push($this->_items, new SurveyEntity($survey));
        }
    }
}

/**
 * Survey question collection
 */
class SurveyPertanyaanCollection extends BaseSurveyCollection
{
    /**
     * Initialize collection from array
     */
    public function __construct(array $pertanyaan_list)
    {
        foreach($pertanyaan_list as $pertanyaan) {
            array_push($this->_items, new SurveyPertanyaan($pertanyaan));
        }
    }
}

/**
 * Survey option collection
 */
class SurveyOpsiCollection extends BaseSurveyCollection
{
    /**
     * Initialize collection from array
     */
    public function __construct(array $opsi_list)
    {
        foreach($opsi_list as $opsi) {
            array_push($this->_items, new SurveyOpsi($opsi));
        }
    }
}

/**
 * Survey entity
 */
class SurveyEntity extends BaseSurvey
{
    public int $id = 0;
    public int $parent_id = 0;
    public int $service_id = 0;
    public int $unit_id = 0;
    public int $referensi_id = 0;
    public string $app = '';
    public string $nama = '';
    public int $level = 1;
    public string $level_label = 'survey';
    public string $status = '';
    public string $date_start = '';
    public string $date_end = '';
    public array $children = [];
    public string $created_at = '';
    public int $created_by = 0;
    public string $modify_at = '';
    public int $modify_by = 0;

    /**
     * Initialize survey entity
     */
    public function __construct(array $item = [])
    {
        if (count($item)) {
            if (($item['level_label'] ?? null) === 'survey' && intval($item['level'] ?? 0) == 1) {
                foreach($item as $key => $val) {
                    $this->{$key} = $val;
                }
            } else {
                throw new \Exception('Invalid survey data');
            }
        }
    }
}

/**
 * Survey question entity
 */
class SurveyPertanyaan extends BaseSurvey
{
    public int $id = 0;
    public int $parent_id = 0;
    public int $service_id = 0;
    public int $unit_id = 0;
    public string $app = '';
    public int $nomor = 0;
    public string $nama = '';
    public int $level = 2;
    public string $level_label = 'pertanyaan';
    public int $survey_id = 0;
    public string $status = '';
    public array $children = [];
    public string $created_at = '';
    public int $created_by = 0;
    public string $modify_at = '';
    public int $modify_by = 0;

    /**
     * Initialize survey question entity
     */
    public function __construct(array $item = [])
    {
        if (($item['level_label'] ?? null) === 'pertanyaan' && intval($item['level'] ?? 0) == 2) {
            foreach($item as $key => $val) {
                $this->{$key} = $val;
            }
        } else {
            throw new \Exception('Invalid pertanyaan data');
        }
    }
}

/**
 * Survey option entity
 */
class SurveyOpsi extends BaseSurvey
{
    public int $id = 0;
    public int $parent_id = 0;
    public int $service_id = 0;
    public int $unit_id = 0;
    public string $app = '';
    public int $nomor = 0;
    public string $nama = '';
    public int $bobot = 0;
    public int $level = 3;
    public string $level_label = 'opsi';
    public int $survey_id = 0;
    public int $pertanyaan_id = 0;
    public string $status = '';
    public string $created_at = '';
    public int $created_by = 0;
    public string $modify_at = '';
    public int $modify_by = 0;

    /**
     * Initialize survey option entity
     */
    public function __construct(array $item = [])
    {
        if (($item['level_label'] ?? null) === 'opsi' && intval($item['level'] ?? 0) == 3) {
            foreach($item as $key => $val) {
                $this->{$key} = $val;
            }
        } else {
            throw new \Exception('Invalid pertanyaan data');
        }
    }
}
