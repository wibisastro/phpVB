<?php namespace Gov2lib;

use DB;
use Exception;
use MeekroDBException;
use WhereClause;

/**
 * -----------------------------------
 * Provide an API for gov2survey app.
 *
 * Class gov2option
 * @package Gov2lib
 * -----------------------------------
 */
class gov2survey
{

    public $table = 'survey_kuesioner_local';

    function __construct ()
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

    final function get_list ($parent_id = 0, $level_label='survey')
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
     * Get a single row from options table.
     *
     * @param array $where
     * @param string $whereType
     * @param string[] $select
     * @return null|array
     */
    final function get ($where=[], $whereType='and', $select=['*'])
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

    final function create (&$data)
    {
        global $doc;
        $connector = new DBConnector($this->dsn);

        try {
            $connector->db->insert($this->table, $data);
            $data = $self->get(['id' => $connector->db->insertId()]);
        } catch (MeekroDBException $e) {
            $doc->exceptionHandler($e->getMessage());
        } catch (Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }
    }
    
    final function update (&$data)
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

    private function collection (&$data, $level_label) 
    {
        $result = [];

        switch($level_label) {
            case 'survey':
                $result = new SurveyCollection($data);
                break;
            case 'pertanyaan':
                $result = new SurveyPertanyaanCollection($data);
                break;
            case 'opsi':
                $result = new SurveyOpsiCollection($data);
                break;
        }
        return $result;
    }
}


class BaseSurveyCollection 
{
    protected $_items = [];

    public function items ()
    {
        return $this->_items;
    }

    public function get ($key)
    {
        $result = null;

        if (is_string($key)) {
            throw new Exception("Invalid key $key");
        }

        if (!is_array($key)) {
            $result = $this->_items[$key];
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
            });

            if ($filtered) {
                $result = $filtered[0];
            }
        }
        return $result;
    }

    public function filter ($key)
    {
        $result = [];

        if (is_string($key) || !is_array($key)) {
            throw new Exception("Invalid key $key");
        }

        $result = array_filter($this->_items, function($item) use($key) {
            $keys = array_keys($key);
            $true = [];

            foreach ($keys as $k) {
                if ($item->{$k} == $key[$k]) {
                    array_push($true, 1);
                }
            }

            if (array_sum($true) > 0) {
                return $item;
            }
        });

        return $result;
    }

    public function serialize ()
    {
        $items = [];

        foreach($this->_items as $i => $item) {
            if (is_object($item) && method_exists($item, 'serialize')) {
                $items[$i] = $item->serialize();
            }
        }

        return $items;
    }

    public function length()
    {
        return count($this->_items);
    }

    public function keys()
    {
        return array_keys($this->_items);
    }
}

class BaseSurvey
{
    public function serialize ()
    {
        $data = (array)$this;
        return $data;
    }

    public function save()
    {
        global $self;

        $this->created_by = $self->ses->val['account_id'];

        if ($this->id) {
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
            switch($data['level_label']) {
                case 'survey':
                    $data = new SurveyEntity($data);
                    foreach($data as $key => $val) {
                        $this->{$key} = $val;
                    }
                    break;
                case 'pertanyaan':
                    $data = new SurveyPertanyaan($data);
                    foreach($data as $key => $val) {
                        $this->{$key} = $val;
                    }
                    break;
                case 'opsi':
                    $data = new SurveyOpsi($data);
                    foreach($data as $key => $val) {
                        $this->{$key} = $val;
                    }
            }
        }
    }

    public function keys ()
    {
        return array_keys($this->serialize());
    }

    private function validate_level ()
    {
        global $self;

        $parent_id = intval($this->parent_id);

        if ($parent_id) {
            $parent = $self->sur->get(['id' => $parent_id]);
        } else {
            $parent = ['level_label' => 'root'];
        }

        switch($parent['level_label'])
        {
            case 'root':
                $this->level = 1;
                $this->level_label = 'survey';
                break;
            case 'survey':
                $this->level = 2;
                $this->level_label = 'pertanyaan';
                break;
            case 'pertanyaan':
                $this->level = 3;
                $this->level_label = 'opsi';
                break;
        }
    }

    private function validate ()
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

        switch($this->level_label) {
            case 'survey':
                $this->parent_id = 0;
                break;
            case 'pertanyaan':
                if (!isset($this->parent_id) || intval($this->parent_id) == 0) {
                    throw new \Exception("Property 'parent_id' should not be empty or 0");
                }

                if (!isset($this->nomor) || !$this->nomor) {
                    throw new \Exception("Property 'nomor' should not be empty or 0");
                }
                break;
            case 'opsi':
                if (!isset($this->parent_id) || intval($this->parent_id) == 0) {
                    throw new \Exception("Property 'parent_id' should not be empty or 0");
                }

                if (!isset($this->nomor) || !$this->nomor) {
                    throw new \Exception("Property 'nomor' should not be empty or 0");
                }

                if (!isset($this->bobot) || !$this->bobot) {
                    throw new \Exception("Property 'bobot' should not be empty or 0");
                }
                break;
        }
    }

    function __call ($name, $args)
    {
        global $self;

        if (!isset($this->id) || !intval($this->id)) {
            return $collection;
        }

        switch ($name) {
            case 'pertanyaan':
                if ($this->level == 1) {
                    return $self->sur->get_list($this->id, 'pertanyaan');
                }
                break;
            case 'opsi':
                if ($this->level == 2) {
                    return $self->sur->get_list($this->id, 'opsi');
                }
                break;
        }
    }
}


class SurveyCollection extends BaseSurveyCollection
{
    function __construct ($survey_list)
    {
        foreach($survey_list as $survey) {
            array_push($this->_items, new SurveyEntity($survey));
        }
    }
}

class SurveyPertanyaanCollection extends BaseSurveyCollection
{
    function __construct ($pertanyaan_list)
    {
        foreach($pertanyaan_list as $pertanyaan) {
            array_push($this->_items, new SurveyPertanyaan($pertanyaan));
        }
    }
}

class SurveyOpsiCollection extends BaseSurveyCollection
{
    function __construct ($opsi_list)
    {
        foreach($opsi_list as $opsi) {
            array_push($this->_items, new SurveyOpsi($opsi));
        }
    }
}


class SurveyEntity extends BaseSurvey
{
    public $id;
    public $parent_id;
    public $service_id;
    public $unit_id;
    public $referensi_id;
    public $app;
    public $nama;
    public $level = 1;
    public $level_label = 'survey';
    public $status;
    public $date_start;
    public $date_end;
    public $children;
    public $created_at;
    public $created_by;
    public $modify_at;
    public $modify_by;

    function __construct($item = [])
    {
        if (count($item)) {
            if ($item['level_label'] === 'survey' && intval($item['level']) == 1) {
                foreach($item as $key => $val) {
                    $this->{$key} = $val;
                }
            } else {
                throw new \Exception('Invalid survey data');
            }
        }
    }
}


class SurveyPertanyaan extends BaseSurvey
{
    public $id;
    public $parent_id;
    public $service_id;
    public $unit_id;
    public $app;
    public $nomor;
    public $nama;
    public $level = 2;
    public $level_label = 'pertanyaan';
    public $survey_id;
    public $status;
    public $children;
    public $created_at;
    public $created_by;
    public $modify_at;
    public $modify_by;

    function __construct($item = [])
    {
        if ($item['level_label'] === 'pertanyaan' && intval($item['level']) == 2) {
            foreach($item as $key => $val) {
                $this->{$key} = $val;
            }
        } else {
            throw new \Exception('Invalid pertanyaan data');
        }
    }
}


class SurveyOpsi extends BaseSurvey
{
    public $id;
    public $parent_id;
    public $service_id;
    public $unit_id;
    public $app;
    public $nomor;
    public $nama;
    public $bobot;
    public $level = 3;
    public $level_label = 'opsi';
    public $survey_id;
    public $pertanyaan_id;
    public $status;
    public $created_at;
    public $created_by;
    public $modify_at;
    public $modify_by;

    function __construct($item = [])
    {
        if ($item['level_label'] === 'opsi' && intval($item['level']) == 3) {
            foreach($item as $key => $val) {
                $this->{$key} = $val;
            }
        } else {
            throw new \Exception('Invalid pertanyaan data');
        }
    }
}
