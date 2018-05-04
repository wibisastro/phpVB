<?php namespace Gov2lib;
/*
Author		: Wibisono Sastrodiwiryo
Date		: 21 Dec 2017
Copyleft	: eGov Lab UI
Contact		: wibi@alumni.ui.ac.id
Version		: 0.0.1
*/
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;

class gov2session extends document {
    function __construct () {
        $this->client = new \GuzzleHttp\Client();
        $this->timeout	= 60*5; #-----5 menit     
        if (isset($_GET['view'])) {
            switch($_GET['view']) {
                case "cases":echo json_encode($cases);break;
		        case "cookie":echo json_encode($_COOKIE);break;
		        case "session":echo json_encode($_SESSION);break;
            }
            exit;
        }
    }
    
    function authenticate ($_privilege="member") {
        global $pageID,$doc;
        $_valid="";
        try {
            if (!isset($_SESSION['account_id']) && $_privilege!="public") {
                throw new \Exception("NotLogin:Page ".strtoupper($pageID)." requires you to login");
            } else {
                if (time()-$_SESSION["started"] > time()+$this->timeout) {
                    throw new \Exception("sessionExpired:Please re-login");
                } else {
                    if ($_privilege=="member" || $_privilege=="webmaster") {
                        $xml=__DIR__.'/../../apps/'.$pageID.'/xml/gov2member.xml';
                        $_members=simplexml_load_file($xml);
                        if (is_array($_members)) {
                            foreach ($_members->member as $_member) {
                                if ($_member->account_id==$_SESSION["account_id"]) {
                                    $_valid=$_member;
                                    break;
                                } else {
                                    throw new \Exception("NotMember:You need to be registered, please contact the admin");
                                }
                            }
                            if (!$_valid->webmaster) {
                                foreach ($_valid->privilege as $cases) {
                                    $_controller = $cases->attributes();
                                    if ($_controller['controller']==$_SERVER['SCRIPT_NAME']) {
                                        break;
                                    } else {
                                        throw new \Exception("UnAuthorized:".$_SERVER['SCRIPT_NAME']);
                                    }
                                }
                            }
                        } else {
                            throw new \Exception("InvalidXMLFile:".$pageID."/xml/gov2member.xml");
                        }
                    }
                    $_SESSION["started"]=time()+$this->timeout;
                    $_SESSION["counter"]++;
//                    $this->cookie_save('started,counter');
                //    } 
                }
            }
        } catch (\Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }
        $this->authorized=$_SESSION;
    }
    /*
    function getdata ($url) {
        global $doc;
        try {
            $res = $this->client->request('GET', $url);
            return $res->getBody();
        } catch (ClientException $e) {
            $doc->error("RequestError",Psr7\str($e->getRequest()));
            $doc->error("ResponseError",Psr7\str($e->getResponse()));
        }        
    }

    function loginAuthenticate ($public) {
        global $doc,$config;
        try {
            $res = $this->client->request('POST', trim($config->platform->apikey),[
                'form_params' => [
                    "cmd"=>"apikey_status",
                    "public"=>trim($public)
                ]
            ]);
            return $res->getBody();
        } catch (ClientException $e) {
            $doc->error("ErrKeyRequest",Psr7\str($e->getRequest()));
            $doc->error("ErrKeyResponse",Psr7\str($e->getResponse()));
        }        
    }
    
    function loginAuthorize ($_publickey) {
        global $doc,$publickey,$self;
        try {
            if ($_SESSION['token']) {
                try {
                    $decoded = \Firebase\JWT\JWT::decode($_SESSION['token'], $publickey, array('HS256'));
                    try {
                        if (in_array($self->className,$decoded->dataset) || in_array("all",$decoded->dataset)) {
                            $self->token=$decoded;
                        } else {
                            throw new \Exception("Unauthorized: Token Anda hanya untuk Dataset ".implode(',', $decoded->dataset));
                        }
                    } catch (\Exception $e) {
                        $this->exceptionHandler($e->getMessage());
                    }
                } catch (\Exception $e) {
                    $doc->error("ErrToken",$e->getMessage());
                }
            } else {
                throw new \Exception('NoSession:Service '.$_SERVER['SERVER_NAME'].' membutuhkan session');
            }
        } catch (\Exception $e) {
            $this->exceptionHandler($e->getMessage());
        }
    }
    
    function createSession ($vars) {
      global $self,$doc,$publickey;
//        try {
            if (!$vars['token']) {
                unset($_SESSION['token']);
                $response["class"]='is-info';
                $response["notification"]="Hapus session berhasil, silahkan refresh browser";
            } else {
                try {
                    $decoded = \Firebase\JWT\JWT::decode($vars['token'], $publickey, array('HS256'));
                    $json=json_decode($this->authenticate($decoded->key));
                    if ($json->comm=='ok') {
                        switch (STAGE) {
                            case "prod":
                            case "build":
                                $domain="https://".$json->domain;
                            break;
                            default:
                                $domain="http://".$json->domain;
                        }
                        if ($domain == $decoded->aud) {
                            $key=file_get_contents($decoded->aud."/gov2api.html");
                            if ($key==$decoded->key) {
                                $_SESSION['token']=$vars['token'];
                            } else {
                                throw new \Exception('IlegalAudience:Domain pengakses bukan Audience kami');
                            }
                        } else {
                            throw new \Exception('InvalidDomain:Domain/Key pengakses tidak valid');
                        }
                    } else {
                        throw new \Exception('UnlistedDomain:Domain pengakses belum terdaftar');
                    }
                    $response["class"]="is-success";
                    $response["notification"]="Exp: ".date("d-m-Y H:i:s", $decoded->exp);
                    $response["callback"]="refreshBrowser";
                } catch (\Exception $e) {
//                    $doc->error("ErrToken",$e->getMessage());
                    $response["class"]="is-danger";
                    $response["notification"]=$e->getMessage();
                }
            }
            return $response;
            */
        /*
        } catch (\Exception $e) {
            $self->exceptionHandler($e->getMessage());
        }
        */
        /*
        if ($doc->error) {
            $response=$doc->response("is-danger");
            header("HTTP/1.1 422 Auth Fail");
        }
        */
    //}
}
?>
