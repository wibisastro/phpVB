<?php namespace App\gov2survey;

class kuesioner extends \Gov2lib\api {
    function __construct()
    {
        global $self, $config;
        $self->ses->authenticate('member');
        parent::__construct();
        $self->takeAll("components");
        $self->takeAll("rokuone");
        $self->scrollInterval=100;
        $self->fields = $self->gov2formfield->getFields(__DIR__ . "/json/kuesioner.json");
        $self->fields_pertanyaan = $self->gov2formfield->getFields(__DIR__ . "/json/kuesioner_pertanyaan.json");
        $self->fields_opsi = $self->gov2formfield->getFields(__DIR__ . "/json/kuesioner_opsi.json");
        // $self->ses->authenticate('maintenance','5.30 AM');
        // var_dump($self->opt);exit;
        // var_dump($config->domain->attr['dsn']);exit;
    }

    function index ()
    {
        global $self,$doc;
        $self->gov2nav->setDefaultNavCustom();
        $doc->body("pageTitle",'Survey');
        $self->loadTable();
        $self->content();
    }

    function fields ($vars)
    {
        global $self;

        switch($vars['id']) {
            case 'kuesioner_opsi':
                $fields = $self->fields_opsi;
                break;
            case 'kuesioner_pertanyaan':
                $fields = $self->fields_pertanyaan;
                break;
            default:
                $fields = $self->fields;
                $cluster = $self->opt->connector_get(['nama' => 'survey', 'app' => trim($vars['app'])]);
                $where = ['app' => trim($vars['app']), 'nama' => 'label', 'parent_id' => intval($cluster['id'])];
                $label = $self->opt->connector_get($where);
                // var_dump($cluster);exit;

                foreach($fields as $i => $field) {
                    if ($field['name'] === 'referensi_id') {
                        if ($label) {
                            $fields[$i]['label'] = $label['value'];
                        }
                        break;
                    }
                }
        }
        return $fields;
    }

    function breadcrumb () {
        global $self, $vars;
        return $self->getBreadcrumb("Kuesioner");
    }

    function table ($vars)
    {
        global $self, $doc;
        $data = $self->browse($vars);
        return $doc->responseGet($data);
    }

    function count ($vars)
    {
        global $self, $doc;
        $parent_id = $self->setRememberId($vars['id']);
        $data = $self->count($parent_id);
        return $doc->responseGet($data);
    }

    function add ()
    {
        global $self, $doc, $scriptID, $cmdID;
        $parent_id = $self->setRememberId(-1);

        $data = array(
            'nama' => $_POST['nama'],
            'parent_id' => $parent_id,
            'unit_id' => $self->dsn_id,
            'app' => trim($scriptID),
            'created_by' => isset($self->ses->val['account_id']) ? $self->ses->val['account_id'] : 0
        );
        
        switch($cmdID) {
            case 'instance_kuesioner_diklat':
                foreach ($self->fields as $key => $field) {
                    if ($field['name'] === 'judul_kuesioner') {
                        $self->fields[$key]['required'] = false;
                    } else if ($field['name'] === 'time_start') {
                        $self->fields[$key]['required'] = false;
                    } else if ($field['name'] === 'time_end') {
                        $self->fields[$key]['required'] = false;
                    }
                }
                $data['nama'] = $_POST['judul_kuesioner'];
                $data['referensi_id'] = intval($_POST['referensi_id']);
                $data['date_start'] = $_POST['date_start'] . ' ' . $_POST['time_start'];
                $data['date_end'] = $_POST['date_end'] . ' ' . $_POST['time_end'];
                $data['parent_id'] = 0;
                $data['status'] = $_POST['status'];
                $data['level'] = 1;

                // $bentrok = $self->jam_tayang_bentrok($data['date_start'], $data['date_end'], $data['app']);
                // if ($bentrok) {
                //     header("HTTP/1.1 422 Invalid Waktu Tayang");
                //     return [
                //         'class' => 'is-warning',
                //         'date_start' => 'Waktu tayang bentrok',
                //         'time_start' => 'Waktu tayang bentrok',
                //         'date_end' => 'Waktu tayang bentrok',
                //         'time_end' => 'Waktu tayang bentrok',
                //         'notification' => "Waktu tayang bentrok dengan kuesioner {$bentrok['nama']}"
                //     ];
                // } else 
                if (date($data['date_end']) <= date($data['date_start'])) {
                    header("HTTP/1.1 422 Invalid Waktu Tayang");
                    return [
                        'class' => 'is-warning',
                        'date_end' => 'Waktu berakhir lebih tidak bisa lebih awal dari waktu mulai',
                        'time_end' => 'Waktu berakhir lebih tidak bisa lebih awal dari waktu mulai',
                        'notification' => "Waktu berakhir lebih tidak bisa lebih awal dari waktu mulai"
                    ];
                }
                break;
            case 'instance_kuesioner_pertanyaan':
                $self->fields = $self->fields_pertanyaan;
                $data['nomor'] = filter_var($_POST['nomor'], FILTER_SANITIZE_NUMBER_INT);
                $data['level'] = 2;
                break;
            case 'instance_kuesioner_opsi':
                $self->fields = $self->fields_opsi;
                $data['nomor'] = filter_var($_POST['nomor'], FILTER_SANITIZE_STRING);
                $data['level'] = 3;
                $data['bobot'] = filter_var($_POST['bobot'], FILTER_SANITIZE_NUMBER_INT);
                break;
        }
        $_POST['parent_id'] = $parent_id;
        $response = $self->postAdd($data);
        return $doc->responseGet($response);
    }

    function edit ($vars) 
    {
        global $self, $doc, $cmdID;
        $data = $self->get($vars['id']);
        if ($cmdID === 'instance_kuesioner_diklat') {
            $data['judul_kuesioner'] = $data['nama'];
            $date_start = explode(' ', $data['date_start']);
            $date_end = explode(' ', $data['date_end']);
            $data['date_start'] = $date_start[0];
            $data['time_start'] = $date_start[1];
            $data['date_end'] = $date_end[0];
            $data['time_end'] = $date_end[1];
            unset($data['nama']);
        }
        return $doc->responseGet($data);
    }

    function update ()
    {
        global $self, $doc, $scriptID, $cmdID;
        $parent_id = $self->setRememberId(-1);

        if (intval($_POST['unit_id']) != $self->dsn_id) {
            header("HTTP/1.1 403 Operation not permitted");
            return [
                'class' => 'warning',
                'notification' => "Tidak dapat mengubah data kuesioner unit lain"
            ];
            exit;
        }

        $data = array(
            'id' => $_POST['id'],
            'parent_id' => $_POST['parent_id'],
            'nama' => filter_var($_POST['nama'], FILTER_SANITIZE_STRING),
            'modify_by' => isset($self->ses->val['account_id']) ? $self->ses->val['account_id'] : 0
        );
        
        switch($cmdID) {
            case 'instance_kuesioner_diklat':
                foreach ($self->fields as $key => $field) {
                    if ($field['name'] === 'judul_kuesioner') {
                        $self->fields[$key]['required'] = false;
                    } else if ($field['name'] === 'time_start') {
                        $self->fields[$key]['required'] = false;
                    } else if ($field['name'] === 'time_end') {
                        $self->fields[$key]['required'] = false;
                    }
                }
                $data['nama'] = $_POST['judul_kuesioner'];
                $data['parent_id'] = 0;
                $data['referensi_id'] = intval($_POST['referensi_id']);
                $data['date_start'] = $_POST['date_start'] . ' ' . $_POST['time_start'];
                $data['date_end'] = $_POST['date_end'] . ' ' . $_POST['time_end'];
                $data['status'] = $_POST['status'];
                $data['level'] = 1;
                $data['level_label'] = 'survey';

                // $bentrok = $self->jam_tayang_bentrok($data['date_start'], $data['date_end'], $data['kerjasama_type']);
                // if ($bentrok && ($bentrok['id'] !== $_POST['id'])) {
                //     header("HTTP/1.1 422 Invalid Waktu Tayang");
                //     return [
                //         'class' => 'is-warning',
                //         'date_start' => 'Waktu tayang bentrok',
                //         'time_start' => 'Waktu tayang bentrok',
                //         'date_end' => 'Waktu tayang bentrok',
                //         'time_end' => 'Waktu tayang bentrok',
                //         'notification' => "Waktu tayang bentrok dengan kuesioner {$bentrok['nama']}"
                //     ];
                // } else 
                if (date($data['date_end']) <= date($data['date_start'])) {
                    header("HTTP/1.1 422 Invalid Waktu Tayang");
                    return [
                        'class' => 'is-warning',
                        'date_end' => 'Waktu berakhir lebih tidak bisa lebih awal dari waktu mulai',
                        'time_end' => 'Waktu berakhir lebih tidak bisa lebih awal dari waktu mulai',
                        'notification' => "Waktu berakhir lebih tidak bisa lebih awal dari waktu mulai"
                    ];
                }
                break;
            case 'instance_kuesioner_pertanyaan':
                $self->fields = $self->fields_pertanyaan;
                $data['nomor'] = filter_var($_POST['nomor'], FILTER_SANITIZE_NUMBER_INT);
                $data['level'] = 2;
                $data['level_label'] = 'pertanyaan';
                break;
            case 'instance_kuesioner_opsi':
                $self->fields = $self->fields_opsi;
                $data['nomor'] = filter_var($_POST['nomor'], FILTER_SANITIZE_STRING);
                $data['level'] = 3;
                $data['level_label'] = 'opsi';
                $data['bobot'] = filter_var($_POST['bobot'], FILTER_SANITIZE_NUMBER_INT);
                break;
        }
        $_POST['parent_id'] = $parent_id;
        $response = $self->postUpdate($data);
        return $doc->responseGet($response);
    }

    function del ()
    {
        global $self, $doc;
        $row = $self->doRead($_POST['id']);
        if (intval($row['unit_id']) != $self->dsn_id) {
            header("HTTP/1.1 403 Operation not permitted");
            $data = $doc->response('danger', 'confirmClose', intval($_POST['id']));
            $data['notification'] = 'Tidak dapat menghapus data kuesioner unit lain';
        } else {
            $data = $self->postDel($_POST);
        }
        return $doc->responseGet($data);
    }

    function countServiceData ($vars)
    {
        global $self, $scriptID;
        
        if (strpos($_SERVER['SERVER_NAME'], 'bkn.go.id') !== false) {
            $domain = 'ppsiasndit.bkn.go.id';
        } else {
            // to dev server
            $domain = 'ppsiasndit.bkn.kl2.web.id';
        }

        $app = 'gov2survey';
        $mvc = 'api';
        $cmd = 'countKuesioner';

        $id = join('-', [$scriptID, (string)$self->dsn_id]);

        $endpoint = join('/', ['http:/',$domain, $app, $mvc, $cmd, $id]);
        
        $resp = $this->getdata($endpoint);
        // var_dump($resp);exit;

        $response = $resp;
        if (array_key_exists('error', $resp)  || array_key_exists('class', $resp)) {
            $response = $resp['error'];
            if(array_key_exists('class', $resp)) {
                $response = $resp;
                $response['callback'] = 'tokenRequired';
            }
        }
        return $response;
    }

    function submit ()
    {
        global $self, $doc, $scriptID;

        if (strpos($_SERVER['SERVER_NAME'], 'bkn.go.id') !== false) {
            $domain = 'ppsiasndit.bkn.go.id';
        } else {
            // to dev server
            $domain = 'ppsiasndit.bkn.kl2.web.id';
        }

        $app = 'gov2survey';
        $mvc = 'api';
        $cmd = 'insert_kuesioner';
        $token = $vars['id'];

        $success = 'Berhasil publish data survey';
        $response = array(
            'class'         => 'success',
            'callback'      => 'infoSnackbar',
            'notification'  => $success
        );

        $req_data = $vars['data'];
        
        $url = join('/', ['http:/',$domain, $app, $mvc]);

        $data_kuesioner = $self->get_submit_data_kuesioner();

        if (is_array($doc->error)) {
            return $doc->response('danger', 'infoSnackbar');
        }

        if (!count($data_kuesioner)) {
            $response = $doc->response('warning', 'infoSnackbar');
            $response['notification'] = 'Belum ada data survey untuk di publish';
            return $response;
        }

        $payload = [
            'cmd' => $cmd,
            'data' => $data_kuesioner
        ];
        
        $resp = $this->putdata($url, $payload);

        // $resp = ['service_id' => {id table service}, 'id' => {local_id}];
        // if any error $resp = ['error' => doc->response('danger', 'infoSnackbar')];
        $resp = json_decode($resp, 1);

        if (array_key_exists('error', $resp)) {
            $response = $resp['error'];
            if(strpos($response['notification'], 'session') !== false) {
                $response = $resp;
                $response['callback'] = 'tokenRequired';
            }
            return $response;
        }

        // update service_id kuesioner
        $self->update_service_id($resp);

        foreach($data_kuesioner as $i => $kuesioner) {
            foreach($resp as $item) {
                if (intval($item['id']) == intval($kuesioner['local_id'])) {
                    $kuesioner['id'] = intval($item['service_id']);
                    break;
                }
            }

            $data_pertanyaan = $self->get_submit_data_child($kuesioner, 'pertanyaan');

            if (count($data_pertanyaan)) {
                $payload['data'] = $data_pertanyaan;
                $resp_pertanyaan = $this->putdata($url, $payload);
                $resp_pertanyaan = json_decode($resp_pertanyaan, 1);

                if (array_key_exists('error', $resp_pertanyaan)) {
                    return $resp_pertanyaan['error'];
                }
                
                // update service_id pertanyaan
                $self->update_service_id($resp_pertanyaan);

                $data_opsis = [];

                foreach($data_pertanyaan as $ii => $pertanyaan) {
                    foreach($resp_pertanyaan as $item) {
                        if (intval($item['id']) == intval($pertanyaan['local_id'])) {
                            $pertanyaan['id'] = intval($item['service_id']);
                            break;
                        }
                    }

                    $data_opsi = $self->get_submit_data_child($pertanyaan, 'opsi');

                    if (count($data_opsi)) {
                        $data_opsis = array_merge($data_opsis, $data_opsi);
                    } else {
                        continue;
                    }
                }

                if (count($data_opsis)) {

                    $chunks = array_chunk($data_opsis, 50);

                    foreach($chunks as $chunk) {
                        $payload['data'] = $chunk;
                        $resp_opsi = $this->putdata($url, $payload);
                        $resp_opsi = json_decode($resp_opsi, 1);

                        if (array_key_exists('error', $resp_opsi)) {
                            return $resp_opsi['error'];
                        }

                        // update service_id opsi
                        $self->update_service_id($resp_opsi);
                    }
                } else {
                    continue;
                }
            } else {
                continue;
            }
        }
        
        return $doc->responseGet($response);
    }

    function reset ($vars)
    {
        global $self, $doc, $scriptID;

        if (strpos($_SERVER['SERVER_NAME'], 'bkn.go.id') !== false) {
            $domain = 'ppsiasndit.bkn.go.id';
        } else {
            // to dev server
            $domain = 'ppsiasndit.bkn.kl2.web.id';
        }

        $app = 'gov2survey';
        $mvc = 'api';
        $cmd = 'getKuesioner';
        $token = $vars['id'];

        $success = 'Berhasil Tarik Data data kuesioner';
        $response = array(
            'class'         => 'success',
            'callback'      => 'infoSnackbar',
            'notification'  => $success
        );

        $req_data = $vars['data'];
        $page = $req_data['page'];

        $id = join('-', [$scriptID, (string)$self->dsn_id]);
        
        $url = join('/', ['http:/',$domain, $app, $mvc, $cmd, $page, $id]);
        $resp = $this->getdata($url);

        if (array_key_exists('error', $resp)) {
            $response = $resp['error'];
            if(strpos($response['notification'], 'session') !== false) {
                $response = $resp;
                $response['callback'] = 'tokenRequired';
            }
            return $doc->response($response);
        }

        $resp_len = count($resp);

        if (!$resp_len) {
            $response = $doc->response('warning', 'infoSnackbar');
            $response['notification'] = 'Belum ada data kuesioner';
        } else {
            $response['count'] = $resp_len;

            foreach($resp as $i => $kuesioner) {
                $self->save_data_service($kuesioner);

                if (is_array($doc->error)) {
                    return $doc->response('danger', 'infoSnackbar');
                }

                $cmd = 'getKuesionerChild';
                $query = [
                    'id' => $kuesioner['service_id'],
                    'local_parent_id' => $kuesioner['id'],
                    'app' => $scriptID,
                    'unit_id' => $self->dsn_id,
                    'level' => 2,
                    'level_label' => 'pertanyaan'
                ];
                
                $url_child = join('/', ['http:/',$domain, $app, $mvc, $cmd]);
                $url_child = $url_child.'?'. http_build_query($query);

                $resp_pertanyaan = $this->getdata($url_child);

                if (array_key_exists('error', $resp_pertanyaan)) {
                    return $resp_pertanyaan['error'];
                }

                if (count($resp_pertanyaan)) {
                    foreach($resp_pertanyaan as $ii => $pertanyaan) {
                        $self->save_data_service($pertanyaan);

                        if (is_array($doc->error)) {
                            return $doc->response('danger', 'infoSnackbar');
                        }

                        $query['survey_id'] = $kuesioner['id'];
                        $query['id'] = $pertanyaan['service_id'];
                        $query['local_parent_id'] = $pertanyaan['id'];
                        $query['level'] = 3;
                        $query['level_label'] = 'opsi';

                        $url_child = join('/', ['http:/',$domain, $app, $mvc, $cmd]);
                        $url_child = $url_child.'?'. http_build_query($query);

                        $resp_answer = $this->getdata($url_child);

                        if (array_key_exists('error', $resp_answer)) {
                            return $resp_answer['error'];
                        }

                        if (count($resp_answer)) {
                            foreach ($resp_answer as $answer) {
                                $self->save_data_service($answer);
                            }
                        }
                    }
                }
            }
        }

        // BEGIN Fetching respondent survey data from service
        $cmd = 'getSurvey';
        $query = [
            'kuesioner_unit_id' => intval($self->dsn_id),
            'app' => $scriptID
        ];
        $url_survey = join('/', ['http:/',$domain, $app, $mvc, $cmd]);
        $url_survey = $url_survey.'?'. http_build_query($query);

        $resp_survey = $this->getdata($url_survey);

        if (array_key_exists('error', $resp_survey)) {
            return $resp_survey['error'];
        }

        $resp_survey_len = count($resp_survey);

        if ($resp_survey_len) {
            foreach($resp_survey as $survey) {
                $self->save_data_survey_service($survey);

                if (is_array($doc->error)) {
                    return $doc->response('danger', 'infoSnackbar');
                }
            }
        }
        // END Fetching respondent survey data from service 

        return $doc->responseGet($response);
    }

    function referensi ($vars)
    {
        global $self, $doc;
        $cluster = $self->opt->connector_get(['nama' => 'survey', 'app' => trim($vars['app'])]);
        $where = ['app' => trim($vars['app']), 'nama' => 'referensi', 'parent_id' => intval($cluster['id'])];
        $option = $self->opt->connector_get($where);
        // echo 'URL : '. $option['value'] .'<br>';
        $data = $this->getdata($option['value']);
        // var_dump($data);exit;
        return $doc->responseGet($data);   
    }
}