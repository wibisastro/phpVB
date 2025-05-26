<?php namespace App\gov2option;

class provider
{
    function mvc()
    {
        global $self, $doc;
        $data = $_POST['data'];
        $portal = $data['portal'];
        $parent_id = isset($data['parent_id']) ? intval($data['parent_id']) : 0;
        $MVCs = $self->getMVCs($portal, $parent_id);

        if (!is_array($MVCs)) {
            header("HTTP/1.0 400 Bad Request");
            header("Content-Type: text/html");
            echo $MVCs;
            exit;
        }

        if (is_array($doc->error)) {
            header("HTTP/1.0 500 Internal server error");
            header("Content-Type: text/html");
            echo $doc->response('danger')['notification'];
            exit;
        }

        return $MVCs;
    }
}