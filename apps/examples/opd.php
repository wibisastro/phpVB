<?php
$doc->takeALl("components");
$scrollInterval=20;
$fields = $gov2formfield->getFields(__DIR__."/json/opd.json");

if ($_POST) {
    switch($_POST["cmd"]) {
        case "add":
            $errors=$gov2formfield->checkRequired($_POST,$fields);
            if (is_array($errors)) {
                $response=$errors;
                $response["class"]="is-warning";
                $response["notification"]="Harap isi form dengan lengkap";
                header("HTTP/1.1 422 Incomplete fields");                    
            } else {
                $id=$self->add($_POST);
                if (!is_array($doc->error)) {
                    $data=$self->read($id);
                    $response=$doc->response("is-primary","toggleForm",$data->id);
                } else {
                    $response=$doc->response("is-danger","toggleForm");
                    header("HTTP/1.1 422 Query Fails");
                }
            }
        break;
        case "del":
            if (!$_POST['id']) {
                $errors['id']='No ID number';
                header("HTTP/1.1 422 Incomplete fields");
                echo json_encode($errors);
                exit;
            } else {
                $self->del($_POST);
                $response=$doc->response("is-primary","confirmClose",(INT)$_POST['id']);
            }
        break;
        case "update":
            $errors=$gov2formfield->checkRequired($_POST,$fields);
            if (is_array($errors)) {
                header("HTTP/1.1 422 Incomplete fields");
                $response=$errors;
            } else {
                $self->update($_POST);
                if (!is_array($doc->error)) {
                    $data=$self->read($_POST['id']);
                    $response=$doc->response("is-info","toggleForm",$data->id);
                } else {
                    $response=$doc->response("is-danger","toggleForm",(INT)$_POST['id']);
                    header("HTTP/1.1 422 Incomplete fields");
                }
            }
        break;
        default:
            header('Access-Control-Allow-Origin: *'); 
    }
    header("Content-type:application/json");
    echo json_encode($response);
    exit;
} else {
    switch($vars["cmd"]) {
        case "count":
            $data=$self->count();
            echo $doc->responseAjax($data);
            exit;
        break;
        case "fields":
            header("Content-type:application/json");
            echo json_encode($fields);
            exit;
        case "table":
            $data=$self->browse($vars['scroll']);
            if (!is_array($data)) {$data=array("data"=>"empty");}
            echo $doc->responseAjax($data);
            exit;
        break;
        case "edit":
            if (!$vars['id']) {
                $errors['id']='Tidak ada nomor ID';
                header("HTTP/1.1 422 Tidak ada nomor ID");
                echo json_encode($errors);
                exit;
            } else {
                $data=$self->read($vars['id']);
                header("Content-type:application/json");
                echo json_encode($data);
                exit;
            }
        break;
        default:
            $doc->body("pageTitle",'Organisasi Perangkat Daerah');

            $self->loadTable($scrollInterval);
            $gov2formfield->content();
            $gov2notification->content();
            $self->content();
            $gov2pagination->content();
    }
}