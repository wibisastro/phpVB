<?php namespace App\gov2survey\model;


class api extends \Gov2lib\crudHandler {
    public $dsn;
    public $dsn_id;

	function __construct () {
	    global $config, $doc;
        $this->templateDir= __DIR__ . "/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $this->controller= __DIR__ . "/gov2survey/" .$this->className.".php";
        parent::__construct();
	}

    function countKuesioner ($unit_id)
    {
        
        global $doc, $uri;

        if (strpos($_SERVER['SERVER_NAME'], 'bkn.go.id')) {
            $dsn = 'ppsiasndit.bkn.go.id';
        } else {
            $dsn = 'ppsiasndit.bkn.kl2.web.id';
        }

        $ids = explode('-', $unit_id);
        $app = $ids[0];
        $unit_id = intval($ids[1]);

        date_default_timezone_set('Asia/Jakarta');
        $now = date('Y-m-d H:i:s');

        $connector = new \Gov2lib\DBConnector($dsn);

        $query_kuesioner = "SELECT COUNT(*) as totalRecord 
                        FROM {$this->tbl->kuesioner_service} 
                        WHERE app='{$app}' 
                            AND unit_id != {$unit_id} 
                            AND level=1 
                            AND level_label='survey' 
                            AND ('{$now}' <= date_end)";

        // $query_survey = "SELECT COUNT(*) as totalRecord 
        //                 FROM {$this->tbl->survey_service} 
        //                 WHERE app='{$app}' AND kuesioner_unit_id = {$unit_id}";
        try {
            $count = $connector->db->queryFirstRow($query_kuesioner, $unit_id);
            // $count_survey = $connector->db->queryFirstRow($query_survey);

            // $count = ['totalRecord' => intval($count_kuesioner['totalRecord']) + intval($count_survey['totalRecord'])];

        } catch (\MeekroDBException $DBException) {
            $this->exceptionHandler($DBException->getMessage().':'.$uri);
            $count = array('error' => $doc->response('danger', 'infoSnackbar'));
        }
        return $count;
    }

    function getKuesioner ($scroll, $unit_id)
    {
        global $doc, $uri;

        if (strpos($_SERVER['SERVER_NAME'], 'bkn.go.id')) {
            $dsn = 'ppsiasndit.bkn.go.id';
        } else {
            $dsn = 'ppsiasndit.bkn.kl2.web.id';
        }

        date_default_timezone_set('Asia/Jakarta');
        $now = date('Y-m-d H:i:s');

        $limit = $this->scroll($scroll);
        $ids = explode('-', $unit_id);
        $app = $ids[0];
        $unit_id = intval($ids[1]);

        $connector = new \Gov2lib\DBConnector($dsn);

        $query_kuesioner = "SELECT id AS service_id, parent_id, unit_id, referensi_id, app, 
                                nomor, nama, bobot, level, level_label, survey_id, pertanyaan_id, 
                                status, date_start, date_end, children, created_at, created_by, 
                                modify_at, modify_by
                            FROM {$this->tbl->kuesioner_service} 
                            WHERE app='{$app}' 
                                AND unit_id != {$unit_id} 
                                AND level=1 
                                AND level_label='survey' 
                                AND ('{$now}' <= date_end)
                            LIMIT {$limit}";

        // $query_survey = "SELECT a.id AS service_id, a.kuesioner_unit_id, a.account_id, 
        //                     survey.local_id AS survey_id, pertanyaan.local_id AS pertanyaan_id, 
        //                     opsi.local_id AS opsi_id, a.unit_id, a.nomor, a.bobot, a.app, a.created_at
        //                 FROM {$this->tbl->survey_service} a
        //                 LEFT JOIN {$this->tbl->kuesioner_service} survey ON survey.id=a.survey_id
        //                 LEFT JOIN {$this->tbl->kuesioner_service} pertanyaan ON pertanyaan.id=a.pertanyaan_id
        //                 LEFT JOIN {$this->tbl->kuesioner_service} opsi ON opsi.id=a.opsi_id
        //                 WHERE a.app='{$app}' 
        //                     AND a.kuesioner_unit_id = {$unit_id}";

        try {
            // $limit = explode(',', $limit);

            $res = $connector->db->query($query_kuesioner);
            // $survey = $connector->db->query($query_survey);

            // $result = array_merge($kuesioner, $survey);

            // $res = array_slice($result, intval($limit[0]), intval($limit[1]));
        } catch (\MeekroDBException $DBException) {
            $this->exceptionHandler($DBException->getMessage().':'.$uri);
            $res = array('error' => $doc->response('danger', 'infoSnackbar'));
        }
        return $res;
    }

    function getKuesionerChild ()
    {
        global $doc, $uri;

        if (strpos($_SERVER['SERVER_NAME'], 'bkn.go.id')) {
            $dsn = 'ppsiasndit.bkn.go.id';
        } else {
            $dsn = 'ppsiasndit.bkn.kl2.web.id';
        }

        $app = $_GET['app'];
        $level_label = $_GET['level_label'];
        $level = intval($_GET['level']);
        $unit_id = intval($_GET['unit_id']);
        $parent_id = intval($_GET['id']);
        $local_parent_id = intval($_GET['local_parent_id']);

        if($level == 2) {
            $survey_id = $local_parent_id;
            $pertanyaan_id = 0;
        } else if($level == 3) {
            $survey_id = $_GET['survey_id'];
            $pertanyaan_id = $local_parent_id;
        }

        $connector = new \Gov2lib\DBConnector($dsn);

        $query = "SELECT id AS service_id, {$local_parent_id} AS parent_id, unit_id, referensi_id, app, 
                    nomor, nama, bobot, level, level_label, {$survey_id} AS survey_id, {$pertanyaan_id} AS pertanyaan_id, 
                    status, date_start, date_end, children, created_at, created_by, 
                    modify_at, modify_by
                FROM {$this->tbl->kuesioner_service} 
                WHERE app='{$app}' 
                    AND unit_id != {$unit_id} 
                    AND level={$level}
                    AND level_label='{$level_label}' 
                    AND parent_id={$parent_id}";

        try {
            $res = $connector->db->query($query);
        } catch (\MeekroDBException $DBException) {
            $this->exceptionHandler($DBException->getMessage().':'.$uri);
            $res = array('error' => $doc->response('danger', 'infoSnackbar'));
        }
        return $res;
    }

    function getSurvey ()
    {
        global $doc, $uri;

        if (strpos($_SERVER['SERVER_NAME'], 'bkn.go.id')) {
            $dsn = 'ppsiasndit.bkn.go.id';
        } else {
            $dsn = 'ppsiasndit.bkn.kl2.web.id';
        }

        $app = $_GET['app'];
        $kuesioner_unit_id = intval($_GET['kuesioner_unit_id']);
        $connector = new \Gov2lib\DBConnector($dsn);

        $query = "SELECT a.id AS service_id, a.kuesioner_unit_id, a.account_id, 
                    survey.local_id AS survey_id, pertanyaan.local_id AS pertanyaan_id, 
                    opsi.local_id AS opsi_id, a.unit_id, a.nomor, a.bobot, a.app, a.created_at
                FROM {$this->tbl->survey_service} a
                LEFT JOIN {$this->tbl->kuesioner_service} survey ON survey.id=a.survey_id
                LEFT JOIN {$this->tbl->kuesioner_service} pertanyaan ON pertanyaan.id=a.pertanyaan_id
                LEFT JOIN {$this->tbl->kuesioner_service} opsi ON opsi.id=a.opsi_id
                WHERE a.app='{$app}' 
                    AND a.kuesioner_unit_id = {$kuesioner_unit_id}";
        try {
            $res = $connector->db->query($query);
        } catch (\MeekroDBException $DBException) {
            $this->exceptionHandler($DBException->getMessage().':'.$uri);
            $res = array('error' => $doc->response('danger', 'infoSnackbar'));
        }
        return $res;
    }

    function insert_survey (&$data)
    {
        global $uri;
        if (strpos($_SERVER['SERVER_NAME'], 'bkn.go.id')) {
            $dsn = 'ppsiasndit.bkn.go.id';
        } else {
            $dsn = 'ppsiasndit.bkn.kl2.web.id';
        }
        $connector = new \Gov2lib\DBConnector($dsn);
        $connector->db->startTransaction();
        try {
            $connector->db->insert($this->tbl->survey_service, $data);
            $insert_id = $connector->db->insertId();
            foreach ($data as $i => $answer) {
                $current_data = $data[$i];
                $data[$i] = [];
                $data[$i]['service_id'] = $insert_id;
                $data[$i]['local_id'] = $current_data['local_id'];
                $insert_id++;
            }
            $connector->db->commit();
        } catch (\MeekroDBException $e) {
            $connector->db->rollback();
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
    }

    function insert_kuesioner (&$data)
    {
        global $uri;
        if (strpos($_SERVER['SERVER_NAME'], 'bkn.go.id')) {
            $dsn = 'ppsiasndit.bkn.go.id';
        } else {
            $dsn = 'ppsiasndit.bkn.kl2.web.id';
        }
        $connector = new \Gov2lib\DBConnector($dsn);
        $insert_data = [];
        $connector->db->startTransaction();
        try {
            
            foreach ($data as $i => $kuesioner) {
                if (intval($kuesioner['id'])) {
                    $this->_update_kuesioner($kuesioner);
                } else {
                    array_push($insert_data, $kuesioner);
                }
            }

            if (count($insert_data)) {
                $connector->db->insert($this->tbl->kuesioner_service, $insert_data);
                $insert_id = $connector->db->insertId();
                foreach($data as $i => $kuesioner) {
                    
                    if (!intval($kuesioner['id'])) {
                        $current_data = $kuesioner;
                        $data[$i] = [];
                        $data[$i]['id'] = $current_data['local_id'];
                        $data[$i]['service_id'] = $insert_id;
                        $insert_id++;
                    } else {
                        unset($data[$i]);
                    }
                }
            } else {
                foreach($data as $i => $kuesioner) {
                    unset($data[$i]);
                }
            }
            $connector->db->commit();
        } catch (\MeekroDBException $e) {
            $connector->db->rollback();
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
    }

    function _update_kuesioner(&$data)
    {
        global $uri;
        if (strpos($_SERVER['SERVER_NAME'], 'bkn.go.id')) {
            $dsn = 'ppsiasndit.bkn.go.id';
        } else {
            $dsn = 'ppsiasndit.bkn.kl2.web.id';
        }
        $connector = new \Gov2lib\DBConnector($dsn);

        $where = [
            'local_id' => $data['local_id'],
            'unit_id' => $data['unit_id'], 
            'app' => $data['app']
        ];

        try {
            $connector->db->update($this->tbl->kuesioner_service, $data, 'id=%i', $data['id']);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
    }
    
}
?>