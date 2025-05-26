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
use GuzzleHttp\Cookie\CookieJar;

class api extends document {
    function __construct ($_dsn="master") {
        parent::__construct();
//        list($_link_id,$_name)=$this->connectDB($_dsn);
//        $this->client = new \GuzzleHttp\Client(['verify' => '/etc/ssl/cacert.pem']);
        $this->client = new \GuzzleHttp\Client(['verify' => false]);
        // session_start(); // -- Commented by Rijal 21 Nov 2023
    }
    
    function getdata ($url, $authorization = false, $bearer_token=null, $include_cookies = false) {
        global $doc, $request;
        $headers = array(
            'headers' => array(
                'Accept' => 'application/json'
            ),
        //    'debug' => true
        );

        if($authorization) {
            $token = $bearer_token ? $bearer_token : $_SESSION['tokenBearer'];
            $headers['headers']['Authorization'] = 'Bearer '.$token;
        }

        if ($include_cookies) {
            $cookie = [
                'Gov2Session' => $_COOKIE['Gov2Session']
            ];
            $jar = CookieJar::fromArray($cookie, $_SERVER['SERVER_NAME']);
            $headers['cookies'] = $jar;
        }

        try {
//        $context = stream_context_create($headers);
//        echo file_get_contents($url);
//            echo file_get_contents("http://coblos.kab.web.id");
            $res = $this->client->request('GET', $url, $headers);
            if ($res) {
//                echo $res->getBody();
                return json_decode($res->getBody(),1);
            } else {
                throw new \Exception('GetFail:'.$url);
            }
        } catch (ClientException $e) {
            $doc->error("RequestError",Psr7\str($e->getRequest()));
            $doc->error("ResponseError",Psr7\str($e->getResponse()));
            $_response = json_decode($e->getResponse()->getBody(), 1);
            if($request === 'ajax') {
                return $_response;
            }
        } catch (\Exception $e) {
        //    $doc->exceptionHandler($e->getMessage());
            $_response = json_decode($e->getMessage(), 1);
            if($request === 'ajax') {
                return $_response;
            }
        }    
    }
    
    function putdata ($url, $data, $authorization = false, $bearer_token=null, $include_cookies = false) {
        global $doc,$config,$self;
        
        $headers = array(
            'form_params' => [
                "cmd"=>$data['cmd'],
                "data"=>$data['data']
            ],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        //    ,'debug'=>true
        );

        if($authorization) {
            $token = $bearer_token ? $bearer_token : $_SESSION['tokenBearer'];
            $headers['headers']['Authorization'] = 'Bearer '.$token;
        }

        if ($include_cookies) {
            $cookie = [
                'Gov2Session' => $_COOKIE['Gov2Session']
            ];
            $jar = CookieJar::fromArray($cookie, $_SERVER['SERVER_NAME']);
            $headers['cookies'] = $jar;
        }

        try {
            $res = $this->client->request('POST', $url, $headers);
            $_result=$res->getBody();
        } catch (ClientException $e) {
            $doc->error("ErrKeyRequest",Psr7\str($e->getRequest()));
            $doc->error("ErrKeyResponse",Psr7\str($e->getResponse()));
            if ($e->hasResponse()) {
                $_result = $e->getResponse()->getBody();
            }
        } catch (\Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }
        return $_result;
    }

    function authenticate ($public) {
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
        } catch (\Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }     
    }
    
    function authorize ($_publickey="") {
        global $doc,$publickey,$self;
        try {
            if ($_SESSION['token']) {
                $this->authorizeToken($_SESSION['token']);
            } else {
                $bearerToken = $this->getBearerToken();
                if ($bearerToken) {
                    $this->authorizeToken($bearerToken);
                } else {
                    throw new \Exception('NoSession:Service '.$_SERVER['SERVER_NAME'].' membutuhkan session');
                }
            }
        } catch (\Exception $e) {
            $this->exceptionHandler($e->getMessage());
        }
    }
    
    function createSession ($vars) {
      global $self,$doc,$publickey, $request;
        
      if ($vars['data']['apikey']) {
          $token = $vars['data']['token'];
      } else {
          $token = $vars['token'];  
      }
//        try {
            if (!$token) {
                unset($_SESSION['token']);
                $response["class"]='is-info';
                $response["notification"]="Hapus session berhasil, silahkan refresh browser";
            } else {
                try {
                    $decoded = $this->decodeJWT($token);
                    $json=json_decode($this->authenticate($decoded->key));
                    if ($json->comm=='ok') {
                        switch (STAGE) {
                            case "kpu":
                            case "drc":
                            case "prod":
                            case "build":
                            case "cybergl":
                                $domain="https://".$json->domain;
                            break;
                            default:
                                $domain="http://".$json->domain;
                        }
                        if ($domain == $decoded->aud) {
                            $key=file_get_contents($decoded->aud."/gov2api.html");
                            if (trim($key)==$decoded->key) {
                                $_SESSION['token']=$token;
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
    }

    /**
     * Get header Authorization
     * */
    function getAuthorizationHeader(){
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }
    /**
     * get access token from header
     * */
    function getBearerToken() {
        $headers = $this->getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * @param string $token
     */
    private function authorizeToken($token) {
        global $publickey, $self, $doc;
        try {
            $decoded = $this->decodeJWT($token);
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
    }

    function decodeJWT($token) {
        global $publickey;
        return \Firebase\JWT\JWT::decode($token, $publickey, array('HS256'));
    }
}
?>
