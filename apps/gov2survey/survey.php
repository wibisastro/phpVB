<?php namespace App\gov2survey;

class survey extends \Gov2lib\api {
    private string $serviceDomain = '';

    function __construct()
    {
        global $self;
        $self->ses->authenticate('guest');
        parent::__construct();
        $self->takeAll("components");
        $self->scrollInterval=100;

        $option = $self->opt->get(['nama' => 'survey_service']);
        $this->serviceDomain = $option['value'] ?? '';
    }

    private function serviceUrl(string ...$parts): string
    {
        return join('/', array_merge(['http:/', $this->serviceDomain, 'gov2survey', 'api'], $parts));
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

        $id = join('-', [$scriptID, (string)$self->dsn_id]);
        $endpoint = $this->serviceUrl('countKuesioner', $id);

        $resp = $this->getdata($endpoint);

        $response = $resp;
        if (array_key_exists('error', $resp) || array_key_exists('class', $resp)) {
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

        $success = 'Berhasil Tarik Data data kuesioner';
        $response = array(
            'class'         => 'success',
            'callback'      => 'infoSnackbar',
            'notification'  => $success
        );

        $req_data = $vars['data'];
        $page = $req_data['page'];

        $id = join('-', [$scriptID, (string)$self->dsn_id]);

        $url = $this->serviceUrl('getKuesioner', $page, $id);
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

                $query = [
                    'id' => $kuesioner['service_id'],
                    'local_parent_id' => $kuesioner['id'],
                    'app' => $scriptID,
                    'unit_id' => $self->dsn_id,
                    'level' => 2,
                    'level_label' => 'pertanyaan'
                ];

                $url_child = $this->serviceUrl('getKuesionerChild');
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

                        $url_child = $this->serviceUrl('getKuesionerChild');
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

        // Fetch respondent survey data from service
        $query = [
            'kuesioner_unit_id' => intval($self->dsn_id),
            'app' => $scriptID
        ];
        $url_survey = $this->serviceUrl('getSurvey');
        $url_survey = $url_survey.'?'. http_build_query($query);

        $resp_survey = $this->getdata($url_survey);

        if (array_key_exists('error', $resp_survey)) {
            return $resp_survey['error'];
        }

        if (count($resp_survey)) {
            foreach($resp_survey as $survey) {
                $self->save_data_survey_service($survey);

                if (is_array($doc->error)) {
                    return $doc->response('danger', 'infoSnackbar');
                }
            }
        }

        return $doc->responseGet($response);
    }

    function submit ($data)
    {
        global $self, $doc;

        $success = 'Berhasil submit data survey';
        $response = array(
            'class'         => 'success',
            'callback'      => 'infoSnackbar',
            'notification'  => $success
        );

        $url = $this->serviceUrl();
        $payload = [
            'cmd' => 'insert_survey',
            'data' => $data
        ];
        $resp = $this->putdata($url, $payload);
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
                $response = $doc->response('warning', 'infoSnackbar');
                $response['notification'] = 'Tidak ada data response service insert_survey';
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
            if (intval($survey['service_id'])) {
                $this->remap_submit_payload($_POST['answers']);
                $this->submit($_POST['answers']);
            }
        }
        return $doc->responseGet($response);
    }

    function remap_submit_payload (&$anwers)
    {
        global $self;

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
