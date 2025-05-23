<?php namespace App\gov2option;


use App\gov2option\fields\field;
use App\gov2option\fields\tokenEntity;
use Firebase\JWT\JWT;

class receiver
{
    function token_option($vars)
    {
        global $self, $doc, $publickey;
        $data = $vars['data'];
        $token_app = $vars['data']['app'];
        $response = array(
            'http_code' => 201,
            'message' => 'OK'
        );

        try {
            $is_exists = $self->is_token_exist($data['token']);

            if ($is_exists) {
                $self->update_counter($data['token']);
                return $response;
            }

            $decoded = JWT::decode($data['token'], $publickey, array('HS256'));
            $token_props = json_decode(json_encode($decoded), true);
            $dataset = json_encode($token_props['dataset']);

            $option = new field();
            $option->nama = $token_props['iss'];
            $exp_desc = date("F j, Y, g:i a", $token_props['exp']);
            $option->keterangan = "Level: {$token_props['datalevel']}, Dataset: {$dataset}, EXP: {$exp_desc}";

            if ($token_app) {
                $option->app = $token_app;
            }

            $option_data = $option->serialize();
            $parent_id = $self->token_option_save($option_data);

            if (!is_array($doc->error)) {
                if ($parent_id) {
                    $token_entity = new tokenEntity();

                    foreach ($token_props as $key => $val) {
                        $token_entity->$key->parent_id = $parent_id;
                        if ($key === 'exp') {
                            $epoch = $val;
                            $dt = new \DateTime("@$epoch");
                            $token_entity->$key->value = $dt->format('Y-m-d H:i:s');
                        } elseif($key === 'dataset'){
                            $token_entity->$key->value = $dataset;
                        } else {
                            $token_entity->$key->value = $val;
                        }
                    }

                    // add custom token props parent_id & value
                    $token_entity->token->value = $data['token'];
                    $token_entity->token->parent_id = $parent_id;
                    $token_entity->suspend->parent_id = $parent_id;
                    $token_entity->kllist->parent_id = $parent_id;
                    $token_entity->counter->parent_id = $parent_id;
                    $token_entity->counter->value = 1;

                    $token_data = $token_entity->serialize();

                    $affected = $self->token_option_save($token_data);

                    if (!$affected) {
                        $notif = $doc->response('danger');
                        $response['http_code'] = 500;
                        $response['message'] = 'Cannot insert token data : '.$notif['notification'];
                    }
                } else {
                    $response['http_code'] = 500;
                    $response['message'] = 'Cannot insert token option';
                }
            } else {
                $notif = $doc->response('danger');
                $response['http_code'] = 500;
                $response['message'] = $notif['notification'];
                $response['data'] = $option_data;
            }

        } catch (\Exception $e) {
            $self->exceptionHandler($e->getMessage());
        }
        return $response;
    }

    function mvc()
    {
        global $self, $doc;
        $data = $_POST['data'];
        $portal = $data['portal'];
        $MVCs = $self->setMVCs($portal, $data['data']);

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