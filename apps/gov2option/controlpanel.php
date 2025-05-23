<?php namespace App\gov2option;

use Gov2lib\api;

class controlpanel extends api {
    function __construct () {
        parent::__construct();
        global $self, $doc;
        $self->takeAll("components");
        $self->ses->authenticate('admin');
        $self->fields = $self->gov2formfield->getFields(__DIR__."/json/option.json");
        $doc->baseBody = '@gov2option/b4body.html';
    }

    function index ($vars) {
        global $self, $doc;

        $self->loadTable($self->scrollInterval);
        $self->content();
    }

    function units($vars)
    {
        global $self, $doc;
        $response = $self->getUnits();
        return $doc->responseGet($response);
    }

    function options ($vars)
    {
        global $self, $doc;
        $portal = $vars['portal'];
        $parent_id = isset($vars['parent_id']) ? intval($vars['parent_id']) : 0;
        $option_row = $self->opt->get(['nama' => 'unit_portal']);
        $endpoint = "{$option_row['value']}/gov2option/provider";
        $payload = array(
          'cmd' => 'mvc',
          'data' => array(
              'portal' => $portal,
              'parent_id' => $parent_id
          )
        );

        $http = $this->putdata($endpoint, $payload);
        $response = json_decode($http, 1);

        if (is_array($doc->error)) {
            return  $doc->response('danger', 'infoSnackbar');
        }

        return $response;
    }

    function save($vars) {
        global $self, $doc;
        $data = $vars['data'];
        $option_row = $self->opt->get(['nama' => 'unit_portal']);
        $endpoint = "{$option_row['value']}/gov2option";
        $payload = array(
            'cmd' => 'mvc',
            'data' => $data
        );

        $http = $this->putdata($endpoint, $payload);
        $response = json_decode($http, 1);

        $error = [];

        foreach ($response AS $row) {
            if (isset($row['updated_message'])) {
                $text = "{$row['nama']} : {$row['updated_message']}";
                array_push($error, $text);
            }
        }

        $N_length = count($data['data']);
        $n_length = $N_length - count($error);
        $notification = "Perubahan data berhasil tersimpan ({$N_length} of {$n_length})";
        if ($error) {
            $error_text = join(', ', $error);
            if ($error_text) {
                $notification .= ". {$error_text}";
            }
        }
        $response = $doc->response('success', 'infoSnackbar');
        $response['notification'] = $notification;

        return $response;
    }
}
