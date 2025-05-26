<?php namespace App\gov2survey\model;

class kuesioner extends \Gov2lib\crudHandler {
    function __construct () {
        global $config, $doc;
        $this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $this->controller=__DIR__."/../".$this->className.".php";
        try {
            $cookies = $doc->envRead($_COOKIE['Gov2Session']);
            $this->dsn = $cookies['portal'];
            $this->dsn_id = $cookies['portal_id'];
            if (!$this->dsn) {
                $this->dsn      = $config->domain->attr['dsn'];
                $this->dsn_id   = $config->domain->attr['id'];
            }
        } catch (\Exception $e) {
            $this->dsn      = $config->domain->attr['dsn'];
            $this->dsn_id   = $config->domain->attr['id'];
        }
        parent::__construct($this->dsn);
        if(explode('.', $_SERVER['SERVER_NAME'])[0] === 'ppsiasndit' && 
            ((string)$this->dsn === 'ppsiasndit.bkn.kl2.web.id' || (string)$this->dsn === 'ppsiasndit.bkn.go.id')) {
            $this->tbl->table = $this->tbl->kuesioner_service;
        } else {
            $this->tbl->table = $this->tbl->kuesioner_local;
        }
    }

    function loadTable ()
    {
        //---gov2pagination
        $GLOBALS['vueData']['itemPerPage'] = 5;
        $GLOBALS['vueData']['interval'] = array(5, 10, 25, 50, 100);
        $GLOBALS['vueData']['scrollInterval'] = $this->scrollInterval;
        //---gov2formfield
        $GLOBALS['vueData']['fieldurl'] = $this->className.'/fields'; //<-overwrite default

        $GLOBALS['vueData']['is_readonly'] = (string)$this->tbl->table === 'survey_kuesioner_service';

        $GLOBALS['vueData']['is_diklat'] = true;
        $GLOBALS['vueData']['is_pertanyaan'] = false;
        $GLOBALS['vueData']['is_opsi'] = false;

        $GLOBALS['vueCreated'] .= 'eventBus.$on("lastLevel", this.switchTable);';
        $GLOBALS['vueMethods'] .= 'switchTable: function(data){
            var parent;
            if(data.data) {
                parent = data.parent;
                data = data.data;
            }
            if(data.level_label === "survey") {
                this.is_diklat = false;
                this.is_pertanyaan = true;
                this.is_opsi = false;
                eventBus.$emit("refreshDatakuesioner_pertanyaan", data.id);
            } else if(data.level_label === "pertanyaan") {
                this.is_diklat = false;
                this.is_pertanyaan = false;
                this.is_opsi = true;
                eventBus.$emit("refreshDatakuesioner_opsi", data.id);
            } else {
                this.is_diklat = true;
                this.is_pertanyaan = false;
                this.is_opsi = false;
                eventBus.$emit("refreshDatakuesioner_diklat");
            }
        },';

        $instances = ['kuesioner_diklat', 'kuesioner_pertanyaan', 'kuesioner_opsi'];

        foreach($instances as $instance){
            $GLOBALS['vueData']['searchQuery'.$instance]='';
            $GLOBALS['vueCreated'].='eventBus.$on("searchQuery'.$instance.'", this.setQuery'.$instance.');';
            $GLOBALS['vueMethods'].='setQuery'.$instance.': function(data) {this.searchQuery'.$instance.'=data;},';

            $GLOBALS['vueData']['scrolls'.$instance]=1;
            $GLOBALS['vueCreated'].='eventBus.$on("setScrolls'.$instance.'", this.setScrolls'.$instance.');';
            $GLOBALS['vueMethods'].='setScrolls'.$instance.': function(data) {this.scrolls'.$instance.'=data;},';

            $GLOBALS['vueData']['itemPerPage'.$instance]=5;
            $GLOBALS['vueCreated'].='eventBus.$on("setItemPerPage'.$instance.'", this.setItemPerPage'.$instance.');';
            $GLOBALS['vueMethods'].='setItemPerPage'.$instance.': function(data) {this.itemPerPage'.$instance.'=data},';

        }
    }

    function browse ($vars)
    {
        global $uri, $scriptID, $cmdID;
        $limit = $this->scroll($vars['scroll']);
        $where = new \WhereClause('and');
        $result = [];
        $parent_id = $this->setRememberId($vars['id']);
        $q = "SELECT *
                FROM {$this->tbl->table} 
                WHERE %l 
                ORDER BY id DESC 
                LIMIT %l";
        
        switch ($cmdID) {
            case 'instance_kuesioner_pertanyaan':
                $where->add('parent_id=%i', $parent_id);
                $where->add('level=%i', 2);
                $where->add('level_label=%s', 'pertanyaan');
                break;
            case 'instance_kuesioner_opsi':
                $where->add('parent_id=%i', $this->ses->val["{$this->className}_id"]);
                $where->add('level=%i', 3);
                $where->add('level_label=%s', 'opsi');
                break;
            default:
            $q = "SELECT a.*, b.nama AS owner 
                FROM {$this->tbl->table} a
                LEFT JOIN {$this->tbl->ref_unitkerja} b ON b.id=a.unit_id
                WHERE %l 
                ORDER BY id DESC 
                LIMIT %l";
                $where->add('a.parent_id=%i', 0);
                $where->add('a.level=%i', 1);
                $where->add('a.level_label=%s', 'survey');
                $where->add('a.app=%s', trim($scriptID));
        }

        try {
            $result = \DB::query($q, $where, $limit);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }

    function count ($parent_id): array
    {
        global $uri;
        $where = new \WhereClause('and');
        $result = [];

        $where->add('parent_id=%i', $parent_id);

        $q = "SELECT count(1) AS totalRecord FROM {$this->tbl->table} WHERE %l ";

        try {
            $result = \DB::queryFirstRow($q, $where);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }

    function get ($id) 
    {
        global $uri;
        $result = null;

        try {
            $result = \DB::queryFirstRow("SELECT * FROM {$this->tbl->table} WHERE id=%i", $id);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }

    function jam_tayang_bentrok ($date_start, $date_end, $app)
    {
        global $uri;
        $result = null;
        $q = "SELECT id, nama 
                FROM {$this->tbl->table} 
                WHERE level=1 AND app='{$app}' AND %l";

        $clause = new \WhereClause('or');

        $clause->add('date_start BETWEEN %s AND %s', $date_start, $date_end);
        $clause->add('date_end BETWEEN %s AND %s', $date_start, $date_end);

        try {
            $result = \DB::queryFirstRow($q, $clause);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage() . ':'.$uri);
        }
        return $result;
    }

    function update_service_id (&$data)
    {
        global $uri;
        $affected = 0;

        try {
            foreach($data as $item) {
                $update_data = ['service_id' => $item['service_id']];
                \DB::update($this->tbl->kuesioner_local, $update_data, 'id=%i', $item['id']);
                $affected += \DB::affectedRows();
            }
            
        } catch(\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $affected;
    }

    function get_submit_data_kuesioner ()
    {
        global $uri, $scriptID;
        $result = [];

        $q = "SELECT id AS local_id, service_id AS id, 0 AS parent_id, unit_id, 
                referensi_id, app, nomor, nama, bobot, level, level_label, survey_id, 
                pertanyaan_id, date_start, date_end, children, created_at, created_by, 
                modify_at, modify_by 
            FROM {$this->tbl->kuesioner_local} 
            WHERE unit_id={$this->dsn_id} AND app='{$scriptID}' AND level_label='survey'
        ";

        try {
            $result = \DB::query($q);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }

    function get_submit_data_child ($parent, $label)
    {
        global $uri, $scriptID;
        $result = [];

        $q = "SELECT id AS local_id, service_id AS id, {$parent['id']} AS parent_id, unit_id, 
                referensi_id, app, nomor, nama, bobot, level, level_label, survey_id, pertanyaan_id, 
                date_start, date_end, children, created_at, created_by, modify_at, modify_by 
            FROM {$this->tbl->kuesioner_local} 
            WHERE unit_id={$this->dsn_id} 
                AND app='{$scriptID}' 
                AND level_label='{$label}' 
                AND parent_id={$parent['local_id']}
        ";

        try {
            $result = \DB::query($q);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }

    function save_data_service (&$item)
    {
        $exist = $this->is_service_data_exists($item['service_id']);

        if ($exist) {
            $item['id'] = intval($exist['id']);
            $this->update($item);
        } else {
            $this->insert($item);
        }
    }

    function save_data_survey_service (&$item)
    {
        $exist = $this->is_service_data_survey_exists($item['service_id']);

        if (!$exist) {
            $this->insert_survey($item);
        }
    }

    function is_service_data_exists ($service_id)
    {
        global $uri;
        $result = null;

        $q = "SELECT * FROM {$this->tbl->kuesioner_local} WHERE service_id=%i";

        try {
            $result = \DB::queryFirstRow($q, $service_id);
        } catch(\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }

    function is_service_data_survey_exists ($service_id)
    {
        global $uri;
        $result = null;

        $q = "SELECT 1 FROM {$this->tbl->survey_local} WHERE service_id=%i";

        try {
            $result = \DB::queryFirstRow($q, $service_id);
        } catch(\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }

    function update ($data)
    {
        global $uri;
        $result = 0;

        try {
            \DB::update($this->tbl->kuesioner_local, $data, 'id=%i', $data['id']);
            $result = \DB::affectedRows();
        } catch(\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }
    
    function insert (&$data)
    {
        global $uri;

        try {
            \DB::insert($this->tbl->kuesioner_local, $data);
            $data['id'] = \DB::insertId();
        } catch(\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
    }

    function insert_survey (&$data)
    {
        global $uri;

        try {
            \DB::insert($this->tbl->survey_local, $data);
            $data['id'] = \DB::insertId();
        } catch(\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
    }
}