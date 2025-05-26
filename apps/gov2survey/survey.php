<?php namespace App\gov2survey;

class survey extends \Gov2lib\api {
    function __construct()
    {
        global $self;
        $self->ses->authenticate('guest');
        parent::__construct();
        $self->takeAll("components");
        $self->takeAll("rokuone");
        $self->scrollInterval=100;
        // $self->fields = $self->gov2formfield->getFields(__DIR__ . "/json/survey.json");
        // $self->ses->authenticate('maintenance','5.30 AM');
    }

    function breadcrumb () {
        global $self, $vars;
        return $self->getBreadcrumb("Survey");
    }

    function index ()
    {
        global $self,$doc;
        $self->gov2nav->setDefaultNavCustom();
        $doc->body("pageTitle",'Survey');
        $self->loadTable();
        $self->content();
    }

    function table ($vars)
    {
        global $self, $doc;
        // \DB::debugMode();
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

    function submit ($data)
    {
        global $self, $doc, $scriptID;

        if (strpos($_SERVER['SERVER_NAME'], 'bkn.go.id') !== false) {
            $domain = 'ppsiasndit.bkn.go.id';
        } else {
            // to dev server
            $domain = 'ppsiasndit.bkn.kl2.web.id';
        }

        // $app = 'hhkbiro';
        // $mvc = 'bulk';
        $app = 'gov2survey';
        $mvc = 'api';
        $cmd = 'insert_survey';
        $token = $vars['id'];

        $success = 'Berhasil submit data survey';
        $response = array(
            'class'         => 'success',
            'callback'      => 'infoSnackbar',
            'notification'  => $success
        );

        $req_data = $vars['data'];
        
        $url = join('/', ['http:/',$domain, $app, $mvc]);
        $payload = [
            'cmd' => $cmd,
            'data' => $data
        ];
        $resp = $this->putdata($url, $payload);
        // echo $url;exit;
        $resp = json_decode($resp, 1);
        
        if (!array_key_exists('error', $resp)) {
            if ($resp) {
                $saved = $self->update_service_id($resp);

                $response['count'] = $saved;
                if (!$saved) {
                    $response = $doc->response('danger', 'infoSnackbar');
                    $response['notification'] ='Gagal update survey_id';
                }
            } else {
                $notif = 'Tidak ada data response service insert_survey';
                $response = $doc->response('warning', 'infoSnackbar');
                $response['notification'] = $notif;
                return $response;
            }
        } else {
            $response = $resp['error'];
            if(strpos($response['notification'], 'session') !== false) {
                $response = $resp;
                $response['callback'] = 'tokenRequired';
            }
        }
        return $doc->responseGet($response);
    }

    function simpan ()
    {
        global $self, $doc;
        $response = $self->simpan($_POST['answers']);

        if ($response['class'] === 'success') {
            $survey = $self->doRead($_POST['answers'][0]['survey_id']);
            // only submit answer if survey has been submitted to service.
            if (intval($survey['service_id'])) {
                $this->remap_submit_payload($_POST['answers']);
                $this->submit($_POST['answers']);
            }
        }
        return $doc->responseGet($response);
    }

    function remap_submit_payload (&$anwers)
    {
        global $self, $doc;

        foreach($anwers as $i => $answer) {
            $survey = $self->doRead($answer['survey_id']);
            $pertanyaan = $self->doRead($answer['pertanyaan_id']);
            $opsi = $self->doRead($answer['opsi_id']);

            $anwers[$i]['survey_id'] = intval($survey['service_id']);
            $anwers[$i]['pertanyaan_id'] = intval($pertanyaan['service_id']);
            $anwers[$i]['opsi_id'] = intval($opsi['service_id']);
        }
    }
}