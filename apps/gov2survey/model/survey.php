<?php namespace App\gov2survey\model;

class survey extends \Gov2lib\crudHandler {
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
        
        $this->tbl->table = $this->tbl->kuesioner_local;
        $this->tbl->survey = $this->tbl->survey_local;
        $this->tbl->kuesioner = $this->tbl->kuesioner_local;
    }

    function loadTable (): void
    {
        //---gov2pagination
        $GLOBALS['vueData']['itemPerPage'] = 10;
        $GLOBALS['vueData']['interval'] = array(10, 25, 50, 100);
        $GLOBALS['vueData']['scrollInterval'] = $this->scrollInterval;
        //---gov2formfield
        $GLOBALS['vueData']['fieldurl'] = $this->className.'/fields'; //<-overwrite default

        $GLOBALS['vueData']['is_survey'] = true;
        $GLOBALS['vueData']['is_pertanyaan'] = false;

        $GLOBALS['vueCreated'] .= 'eventBus.$on("lastLevel", this.switchTable);';
        $GLOBALS['vueMethods'] .= 'switchTable: function(data){
            var parent;
            if(data.data) {
                parent = data.parent;
                data = data.data;
            }
            if(data.level_label === "survey") {
                this.is_survey = false;
                this.is_pertanyaan = true;
                eventBus.$emit("refreshDatasurvey_pertanyaan", data.id);
            } else {
                this.is_survey = true;
                this.is_pertanyaan = false;
                eventBus.$emit("refreshDatasurvey_survey");
            }
        },';

        $instances = ['survey_survey', 'survey_pertanyaan'];

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
        $portal = explode('.', $_SERVER['SERVER_NAME'])[0];
        $limit = $this->scroll($vars['scroll']);

        date_default_timezone_set('Asia/Jakarta');
        $now = date('Y-m-d H:i:s');

        $parent_id = $this->setRememberId($vars['id']);

        $query_kuesioner = "SELECT id, nama AS kuesioner, date_start, date_end, unit_id 
                                FROM {$this->tbl->kuesioner} 
                                WHERE level=1 AND level_label='survey' AND app='{$scriptID}' AND status='active'
                                    AND ('{$now}' BETWEEN date_start AND date_end) AND id=%i
                                ORDER BY id DESC LIMIT 1";

        $query_pertanyaan = "SELECT * 
                                FROM {$this->tbl->kuesioner} 
                                WHERE parent_id=%i 
                                AND level_label=%s ORDER BY id, nomor ASC";

        $query_opsi = "SELECT a.id, a.parent_id, a.survey_id, a.pertanyaan_id, a.nama, a.nomor, a.created_at, a.bobot,
                                b.opsi_id AS jawaban_id, '{$scriptID}' AS app, %i AS kuesioner_unit_id 
                            FROM {$this->tbl->kuesioner} a
                            LEFT JOIN {$this->tbl->survey} b 
                                ON b.survey_id=a.survey_id 
                                    AND b.pertanyaan_id=a.pertanyaan_id 
                                    AND b.account_id=%s
                                    AND b.unit_id={$this->dsn_id}
                            WHERE a.parent_id=%i AND a.level_label=%s
                            ORDER BY a.nomor ASC";


        try {
            switch($cmdID) {
                case 'instance_survey_survey':
                    $query_kuesioner = "SELECT a.id, a.nama, a.date_end, b.nama as owner 
                                    FROM {$this->tbl->kuesioner} a
                                    LEFT JOIN {$this->tbl->ref_unitkerja} b ON b.id=a.unit_id 
                                    WHERE a.level=1 AND a.level_label='survey' AND a.app='{$scriptID}' AND a.status='active'
                                        AND ('{$now}' BETWEEN a.date_start AND a.date_end)
                                    ORDER BY a.id DESC LIMIT {$limit}";
                    $result = \DB::query($query_kuesioner);
                    break;
                default:
                    $result = \DB::queryFirstRow($query_kuesioner, $parent_id);

                    if ($result) {
                        $result['pertanyaan'] = \DB::query($query_pertanyaan, $result['id'], 'pertanyaan');

                        foreach($result['pertanyaan'] as $index => $pertanyaan) {
                            $opsi = \DB::query($query_opsi, $result['unit_id'], $this->ses->val['account_id'], $pertanyaan['id'], 'opsi');
                            $result['pertanyaan'][$index]['opsi'] = $opsi;

                            if ($opsi[0]['jawaban_id']) {
                                $result['telah_mengisi'] = true;
                            } else {
                                $result['telah_mengisi'] = false;
                            }
                        }
                    } else {
                        $result = [
                            'pertanyaan' => [],
                            'telah_mengisi' => false
                        ];
                    }
            }
            
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }

    function count($parent_id)
    {
        global $uri;
        $where = new \WhereClause('and');
        $result = [];

        $where->add('parent_id=%i', $parent_id);

        $q = "SELECT count(1) AS totalRecord FROM {$this->tbl->kuesioner} WHERE %l ";

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
            $result = \DB::queryFirstRow("SELECT * FROM {$this->tbl->survey} WHERE id=%i", $id);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }

    function simpan (&$anwers)
    {
        global $uri, $doc;

        foreach($anwers as $i => $answer) {
            $anwers[$i]['account_id'] = $this->ses->val['account_id'];
            $anwers[$i]['unit_id'] = $this->dsn_id;
        }
        \DB::startTransaction();

        try {
            \DB::insert($this->tbl->survey, $anwers);
            $resp = $doc->response('success', 'infoSnackbar');
            $insert_id = \DB::insertId();
            foreach($anwers as $i => $answer) {
                $anwers[$i]['local_id'] = $insert_id;
                $insert_id++;
            }
            \DB::commit();
        } catch(\MeekroDBException $e) {
            \DB::rollback();
            $this->exceptionHandler($e->getMessage().':'.$uri);
            $resp = $doc->response('danger', 'infoSnackbar');
        }
        return $resp;
    }

    function update_service_id (&$data)
    {
        global $uri;
        $affected = 0;

        try {
            foreach($data as $answer) {
                $update_data = ['service_id' => $answer['service_id']];
                \DB::update($this->tbl->survey, $update_data, 'id=%i', $answer['local_id']);
                $affected += \DB::affectedRows();
            }
            
        } catch(\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $affected;
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