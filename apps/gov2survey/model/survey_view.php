<?php namespace App\gov2survey\model;

class survey_view extends \Gov2lib\crudHandler {
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
            $this->tbl->survey = $this->tbl->survey_service;
        } else {
            $this->tbl->table = $this->tbl->kuesioner_local;
            $this->tbl->survey = $this->tbl->survey_local;
        }
    }

    function loadTable (): void
    {
        //---gov2pagination
        $GLOBALS['vueData']['itemPerPage'] = 5;
        $GLOBALS['vueData']['interval'] = array(5, 10, 25, 50, 100);
        $GLOBALS['vueData']['scrollInterval'] = $this->scrollInterval;
        //---gov2formfield
        $GLOBALS['vueData']['fieldurl'] = $this->className.'/fields'; //<-overwrite default

        $GLOBALS['vueData']['is_diklat'] = true;
        $GLOBALS['vueData']['is_pertanyaan'] = false;

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
                eventBus.$emit("refreshDatasurvey_pertanyaan", data.id);
            } else {
                this.is_diklat = true;
                this.is_pertanyaan = false;
                eventBus.$emit("refreshDatasurvey_view");
            }
        },';

        $instances = ['survey_view', 'survey_pertanyaan'];

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
        global $uri, $cmdID;
        $limit = $this->scroll($vars['scroll']);
        $result = [];
        $parent_id = $this->setRememberId($vars['id']);

        try {
            switch($cmdID) {
                case 'instance_survey_pertanyaan':
                    $kuesioner = $this->get_kuesioner($limit, $parent_id);
                    $pertanyaans = $this->get_pertanyaan($kuesioner['id']);
                    $respondent = $this->count_respondent($kuesioner['id']);

                    $result['survey'] = $kuesioner['nama'];
                    $result['responden'] = intval($respondent['respondent']);
                    $result['categories'] = array_column($pertanyaans, 'nama');
                    $result['series'] = [];

                    foreach($pertanyaans as $i => $pertanyaan) {
                        $pertanyaans[$i]['average'] = 0;
                        $answers = $this->get_opsi($kuesioner['id'], $pertanyaan['id']);

                        foreach($answers as $answer) {
                            $survey = $this->get_survey($kuesioner['id'], $pertanyaan['id'], $answer['id']);
                            $votes = count($survey);

                            switch($answer['nomor']) {
                                case 'a':
                                    if(!in_array('A', array_column($result['series'], 'name'))) {
                                        $result['series'][0] = [
                                            'name' => 'A',
                                            'data' => []
                                        ];
                                    }
                                    // $result['series'][0]['data'][$i] = $votes / $result['responden'] * 100;
                                    $result['series'][0]['data'][$i] = $votes;
                                    break;
                                case 'b':
                                    if(!in_array('B', array_column($result['series'], 'name'))) {
                                        $result['series'][1] = [
                                            'name' => 'B',
                                            'data' => []
                                        ];
                                    }
                                    // $result['series'][1]['data'][$i] = $votes / $result['responden'] * 100;
                                    $result['series'][1]['data'][$i] = $votes;
                                    break;
                                case 'c':
                                    if(!in_array('C', array_column($result['series'], 'name'))) {
                                        $result['series'][2] = [
                                            'name' => 'C',
                                            'data' => []
                                        ];
                                    }
                                    // $result['series'][2]['data'][$i] = $votes / $result['responden'] * 100;
                                    $result['series'][2]['data'][$i] = $votes;
                                    break;
                                case 'd':
                                    if(!in_array('D', array_column($result['series'], 'name'))) {
                                        $result['series'][3] = [
                                            'name' => 'D',
                                            'data' => []
                                        ];
                                    }
                                    // $result['series'][3]['data'][$i] = $votes / $result['responden'] * 100;
                                    $result['series'][3]['data'][$i] = $votes;
                                    break;
                                case 'e':
                                    if(!in_array('E', array_column($result['series'], 'name'))) {
                                        $result['series'][4] = [
                                            'name' => 'E',
                                            'data' => []
                                        ];
                                    }
                                    // $result['series'][4]['data'][$i] = $votes / $result['responden'] * 100;
                                    $result['series'][4]['data'][$i] = $votes;
                                    break;
                            }
                            $pertanyaans[$i]['average'] += $votes * intval($answer['bobot']);
                        }

                        if ($pertanyaans[$i]['average'] > 0) {
                            $pertanyaans[$i]['average'] = $pertanyaans[$i]['average'] / $result['responden'];
                        }
                    }

                    $result['pertanyaan'] = $pertanyaans;
                    break;
                default:
                    $kuesioners = $this->get_kuesioner($limit);

                    foreach ($kuesioners AS $i => $kuesioner) {
                        $max_weight = 0;

                        $pertanyaans = $this->get_pertanyaan($kuesioner['id']);
                        $respondents = $this->count_respondent($kuesioner['id']);

                        $kuesioners[$i]['pertanyaan'] = count($pertanyaans);
                        $kuesioners[$i]['responden'] = intval($respondents['respondent']);
                        $kuesioners[$i]['score'] = 0;

                        foreach ($pertanyaans AS $ii => $pertanyaan) {
                            $answers = $this->get_opsi($kuesioner['id'], $pertanyaan['id']);

                            $pertanyaans[$ii]['average'] = 0;

                            foreach($answers as $iii => $answer) {
                                $survey = $this->get_survey($kuesioner['id'], $pertanyaan['id'], $answer['id']);
                                $votes = count($survey);
                                $answers[$iii]['votes'] = $votes;

                                if (intval($answer['bobot']) > $max_weight) {
                                    $max_weight = intval($answer['bobot']);
                                }
                                
                                $pertanyaans[$ii]['average'] += $votes * intval($answer['bobot']);
                            }

                            if ($pertanyaans[$ii]['average'] > 0) {
                                $pertanyaans[$ii]['average'] = $pertanyaans[$ii]['average'] / $kuesioners[$i]['responden'];
                                $kuesioners[$i]['score'] += $pertanyaans[$ii]['average'];
                            }
                        }

                        $kuesioners[$i]['pertanyaans'] = $pertanyaans;

                        if ($kuesioners[$i]['score'] > 0) {
                            $kuesioners[$i]['score'] = round($kuesioners[$i]['score'] / $kuesioners[$i]['pertanyaan'], 2);
                        }

                        $kuesioners[$i]['max_score'] = $max_weight;
                    }
                    $result = $kuesioners;
            }
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }

    function get_kuesioner ($limit, $id=0)
    {
        global $uri, $scriptID;
        $result = [];

        date_default_timezone_set('Asia/Jakarta');
        $now = date('Y-m-d H:i:s');

        $q = "
            SELECT a.*, b.nama AS owner 
            FROM {$this->tbl->table} a
            LEFT JOIN {$this->tbl->ref_unitkerja} b ON b.id=a.unit_id
            WHERE a.level=1 
                AND a.level_label='survey' 
                AND a.app='{$scriptID}'
                AND ('{$now}' > a.date_end)
        ";

        try {
            if (intval($id) > 0) {
                $q .= " AND a.id=%i";
                $result = \DB::queryFirstRow($q, $id);
            } else {
                $q .= " LIMIT {$limit}";
                $result = \DB::query($q);
            }
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }
    
    function get_pertanyaan ($kuesioner_id, $id=0)
    {
        global $uri;
        $result = [];

        $q = "
            SELECT * 
            FROM {$this->tbl->table} 
            WHERE level=2 
                AND level_label='pertanyaan' 
                AND survey_id=%i
        ";

        try {
            if (intval($id) > 0) {
                $q .= " AND id=%i";
                $result = \DB::queryFirstRow($q, $kuesioner_id, $id);
            } else {
                $result = \DB::query($q, $kuesioner_id);
            }
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }
    
    function get_opsi ($kuesioner_id, $pertanyaan_id, $id=0)
    {
        global $uri;
        $result = [];

        $q = "
            SELECT * 
            FROM {$this->tbl->table} 
            WHERE level=3 
                AND level_label='opsi' 
                AND survey_id=%i
                AND pertanyaan_id=%i
        ";

        try {
            if (intval($id) > 0) {
                $q .= " AND id=%i";
                $result = \DB::queryFirstRow($q, $kuesioner_id, $pertanyaan_id, $id);
            } else {
                $result = \DB::query($q, $kuesioner_id, $pertanyaan_id);
            }
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }

    function get_survey ($kuesioner_id, $pertanyaan_id, $opsi_id)
    {
        global $uri;
        $result = [];

        $q = "
            SELECT * 
            FROM {$this->tbl->survey} 
            WHERE survey_id=%i 
                AND pertanyaan_id=%i 
                AND opsi_id=%i
        ";

        try {
            $result = \DB::query($q, $kuesioner_id, $pertanyaan_id, $opsi_id);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }
    
    function count ($parent_id)
    {
        global $uri, $scriptID;
        $where = new \WhereClause('and');
        $result = [];

        $where->add('parent_id=%i', $parent_id);
        $where->add('app=%s', $scriptID);

        $q = "SELECT COUNT(1) AS totalRecord FROM {$this->tbl->table} WHERE %l ";

        try {
            $result = \DB::queryFirstRow($q, $where);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }

    function count_respondent ($kuesioner_id)
    {
        global $uri;
        $result = ['respondent' => 0];
        $q = "
            SELECT COUNT(DISTINCT account_id, unit_id ) AS respondent 
            FROM {$this->tbl->survey} 
            WHERE survey_id=%i 
        ";

        try {
            $result = \DB::queryFirstRow($q, $kuesioner_id);
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
}