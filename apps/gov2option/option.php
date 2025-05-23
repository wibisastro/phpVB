<?php namespace App\gov2option;

class option {
    function __construct () {
        global $self, $doc;
        $self->takeAll("components");
        $self->ses->authenticate('admin');
        $self->fields = $self->gov2formfield->getFields(__DIR__."/json/option.json");
        $doc->baseBody = '@gov2option/b4body.html';
    }
    
    function index ($vars) {
        global $self, $doc;
        if ($vars["privilege"] == "setup") {
            $self->loadTable($self->scrollInterval);
            $self->content();
        } elseif ($vars["privilege"] == "view") {
            $doc->body('app', $vars['pageID']);
            $self->content('option_view.html');
        } elseif ($vars["privilege"] == "view_services") {
            $doc->body('app', $vars['pageID']);
            $self->content('option_view_service.html');
        }
    }

    function fields () {
        global $self;
        return $self->fields;
    }

    function breadcrumb () {
        global $self;
        return $self->getBreadcrumb("Data Options");
    }
    
    function table ($vars) {
        global $doc,$self;
        $data=$self->getRecords($vars);
        if (sizeof($data)==0) {$data=array("data"=>"empty","level"=>"1");}
        return $doc->responseGet($data);   
    }

    function add () {
        global $self, $doc, $scriptID;
        unset($_POST['id']);
        unset($_POST['cmd']);
        unset($_POST['children']);
        unset($_POST['created_at']);
        unset($_POST['created_by']);
        $_POST['created_by'] = $self->ses->val['account_id'];
        $_POST['app'] = $scriptID;
        $_POST['level'] = 1;
        $_POST['parent_id'] = 0;
        $_POST['level_label'] = 'cluster';
        $_id = $self->setRememberId('-1');
        if (intval($_id)) {
            $_POST['level'] = 2;
            $_POST['parent_id'] = intval($_id);
            $_POST['level_label'] = 'option';
        }
        $response = $self->postAdd($_POST);
        return $response;
    }

    function del () {
        global $self;
        $response=$self->postDel($_POST);
        return $response;
    }

    function edit ($vars) {
        global $self;
        return $self->getRecord($vars['id']);
    }

    function update () {
        global $self;
        $currentUser = $self->ses->val['account_id'];

        if (isset($_POST['modify_by']) === false || $_POST['modify_by'] == '0') {
            $_POST['modify_by'] = $currentUser;
        }
        $response=$self->postUpdate($_POST);
        return $response;
    }

    function count ($vars) {
        global $self,$doc;
        $data=$self->getCount($vars['id']);
        return $doc->responseGet($data);
    }

    function options($vars) {
        global $self, $doc;
        $id = $vars['id'];
        $data=$self->getOptions($id);
        return $doc->responseGet($data);
    }

    function services($vars) {
        global $self, $doc;
        $id = $vars['id'];
        $data=$self->getOptions($id, 'service');
        return $doc->responseGet($data);
    }

    function service_expiry($vars) {
        global $self, $doc;
        $id = $vars['id'];
        $data=$self->getExpiry($id);
        return $doc->responseGet($data);
    }

    function service_del($vars) {
        global $self, $doc;
        $id = $vars['id'];
        $self->service_del($id);
        if (is_array($doc->error)) {
            $response = $doc->response('danger', 'infoSnackbar');
        } else {
            $response = $doc->response('success', 'infoSnackbar');
        }
        return $response;
    }

    function save($vars) {
        global $self, $doc;
        $response = [
            'class' => 'success',
            'callback' => 'infoSnackbar',
            'id' => 0,
            'notification' => 'Berhasil menyimpan perubahan.'
        ];
        $self->saveItems($vars['data']);
        if (is_array($doc->error)) {
            $response = $doc->response('danger', 'infoSnackbar');
        }
        return $response;
    }

    function save1($vars) {
        global $self, $doc;
        $response = [
            'class' => 'success',
            'callback' => 'infoSnackbar',
            'id' => 0,
            'notification' => 'Berhasil menyimpan perubahan.'
        ];
        foreach ($vars['data'] as $key => $row) {
            if ($row['value'] === true) {
                $vars['data'][$key]['value'] = 1;
            } elseif ($row['value'] === false) {
                $vars['data'][$key]['value'] = "";
            }
        }
        $self->saveItems($vars['data']);
        if (is_array($doc->error)) {
            $response = $doc->response('danger', 'infoSnackbar');
        }
        return $response;
    }
}
