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

class gov2session extends dsnSource {
    function __construct ($_auth="") {
        parent::__construct();
        $this->client = new \GuzzleHttp\Client();
        $this->timeout	= time()+(24*60*60); #-----1 hari
        if (isset($_GET['view'])) {
            switch($_GET['view']) {
                case "cases":echo json_encode($cases);break;
		        case "cookie":echo json_encode($_COOKIE);break;
		        case "session":echo json_encode($_SESSION);break;
            }
            exit;
        }
        if ($_COOKIE['Gov2Session']) {
            $this->sesRead($_COOKIE['Gov2Session']);
        } else {
            if ($_auth["cmd"]!='sessave') { 
                $_token['userRole']="public";
//                $this->sesSave($_token,1);
            }
        }
    }
    
    function sesReset () {
        global $publickey;
        unset($_COOKIE['Gov2Session']);
        setcookie("Gov2Session");
    }
    
    function sesSave ($data,$redirect=0) {
        global $publickey;
        if ($_COOKIE['Gov2Session']) {
            $_token = $_COOKIE['Gov2Session'];
        } else {
            $_data['userRole']="public";
            $_token = \Firebase\JWT\JWT::encode($_data,$publickey);
        }
        $_existing = \Firebase\JWT\JWT::decode($_token, $publickey, array('HS256'));
        $_data = array_merge((array)$_existing,(array)$data);
        $_token = \Firebase\JWT\JWT::encode($_data,$publickey);
        setcookie("Gov2Session", $_token, $this->timeout,"/");
        if ($redirect) { 
            header('location: /');
            exit;
        }
    }
    
    function sesRead ($data) {
        global $publickey;
        $_result=\Firebase\JWT\JWT::decode($data, $publickey, array('HS256'));
        $this->val=json_decode(json_encode($_result), true);
//        $GLOBALS['_GOV2SES']=(array)$_result;
    }
    
    function authenticate ($_privilege="member", $_maintenance="") {
        global $pageID,$doc,$config;
        $_valid="";
        try {
            if (STAGE!='dev') {
				if ($config->domain->attr['shift'] && $config->domain->attr['shift'] != date("A") && $config->domain->attr['shift']!='ALL') {
                    throw new \Exception("WrongTime:Portal ini hanya dapat dibuka pada waktu ".$config->domain->attr['shift']);
                } elseif (!isset($this->val['account_id']) && $_privilege!="public") {
                    throw new \Exception("NotLogin:Halaman ".strtoupper($pageID)." harus login terlebih dahulu");
                } else {
                    $doc->body['_SESSION']=(array)$this->val;

                    if (!$this->val['id'] && $_privilege!='public') {
                        $_member=$this->memberRead($this->val['account_id']);
                        if ($_member['id']) {
                            $_gov2session['id']=$_member['id'];
                            $_gov2session['userRole']=$_member['role'];
                            $_gov2session['status']=$_member['status'];
                            $this->sesSave($_gov2session,1);
                        }
                    } else {
                        $doc->body['_SESSION["userRole"]']=$this->val['userRole'];
//                        $this->memberUpdateCounter($this->val['id']);
                        switch ($this->val['status']) {
                            case "pending":
                                throw new \Exception("Pending:Akun Anda belum aktif, silahkan aktivasi terlebih dahulu");
                            break;
                            case "suspended":
                                throw new \Exception("Suspended:Akun Anda terblokir, silakan hubungi Admin");
                            break;
                            default:
                                $_userRole=array('guest'=>'1', 
                                        'member'=>'2', 
                                        'admin'=>'3',
                                        'webmaster'=>'4',
                                        'owner'=>'5',
                                        'developer'=>'6');
                                $_pageRole=array('guest'=>'1',
                                        'member'=>'2',
                                        'admin'=>'3',
                                        'webmaster'=>'4',
                                        'closed'=>'5',
                                        'maintenance'=>'6');
                                if ($_userRole[$this->val['userRole']]<$_pageRole[$_privilege] && $_privilege!='closed' && $_privilege!='maintenance') {
                                    throw new \Exception("Unauthorized:Akun Anda tidak memiliki wewenang, silakan hubungi Admin");
                                } elseif ($_userRole[$this->val['userRole']]<$_pageRole[$_privilege] && $_privilege=='closed') {
                                    throw new \Exception("Closed:Menu ini ditutup");
                                } elseif ($_userRole[$this->val['userRole']]<$_pageRole[$_privilege] && $_privilege=='maintenance') {
                                    throw new \Exception("Maintenance:System sedang dalam peningkatan kapasitas hingga jam ".$_maintenance);
                                }
                        }
                    }
                }
            } else {
                $doc->body("_SESSION['fullname']",'Development');
                $doc->body("_SESSION['account_id']']",'-1');
            }
        } catch (\Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }
//        $this->authorized=$_gov2session;
    }
    
    function memberUpdateCounter($id) {
        try {
            $_query="UPDATE member SET counter=counter+1,lastlogin_at=NOW() WHERE id=%i";
            \DB::query($_query,$id);
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage());
		}
	}
    
    function memberRead ($id=0) {
        global $doc;
        try {
            $query="SELECT * FROM member WHERE account_id=%i";
//            echo \DB::$host;
            $result=\DB::queryFirstRow($query,$id);
            if (!is_array($result)) {
                $_id=$this->insertMember();
                $query="SELECT * FROM member WHERE account_id=%i";
                $result=\DB::queryFirstRow($query,$_id);
            }
        } catch (\MeekroDBException $e) {
			$doc->exceptionHandler($e->getMessage());
		}
	return $result;
	}
    
    function insertMember () {
        global $doc,$config;
//        $_attr=$config->domain->{$_SERVER["SERVER_NAME"]}->attributes();
        try {
            if ($this->val['account_id'] == '14' || $this->val['account_id'] == '138') {$_role='developer';}
            else {$_role='guest';}
            if ($config->domain->attr['level']==1) {
                $_kab_id=0;
                $_prov_id=trim($config->domain->attr['id']);
            } else {
                $_kab_id=trim($config->domain->attr['id']);
                $_prov_id=$this->wilayahRead(trim($config->domain->attr['id']));
                $_prov_id+=0;
            }
            $_fields=array('account_id' => $this->val['account_id'],
                'fullname' => $this->val['fullname'],
                'email' => $this->val['email'],
                'status' => "active",
                'role' => $_role,
                'counter' => "1",
                'lastlogin_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'kab_id' => $_kab_id,
                'prov_id' => $_prov_id 
             );
            \DB::insert("member", $_fields);
            return \DB::insertId();
        } catch (\MeekroDBException $e) {
			$doc->exceptionHandler($e->getMessage());
		} catch (\Exception $e) {
			$doc->exceptionHandler($e->getMessage());
		}
	}
    
    function wilayahRead ($kab_id) {
        global $doc;
        $_query="SELECT * FROM wilayah WHERE id=%i";
        try {
            $result=\DB::queryFirstRow($_query,$kab_id);
        } catch (\MeekroDBException $e) {
        	$doc->exceptionHandler($e->getMessage());
		}
        return $result['parent_id'];
	}
    
    function authorize ($wilayah_id) {
        global $pageID,$doc;
        $_valid="";
        try {
            if (STAGE!='dev') {
                if (!$this->val['privilege']) {
                    $_privilege=$this->privilegeRead($wilayah_id);
//                    $_gov2session['privilege']=$_privilege;
                    $this->val['privilege']=$_privilege;
//                    $this->sesSave($_gov2session);
                    $this->sesSave($this->val);
                } elseif ($this->val['privilege']['wilayah_id']!=$wilayah_id) {
                    unset($this->val['privilege']);
                    $_privilege=$this->privilegeRead($wilayah_id);
//                    $_gov2session['privilege']=$_privilege;
//                    $this->sesSave($_gov2session);
                    $this->val['privilege']=$_privilege;
                    $this->sesSave($this->val);
                }
                if ($this->val['privilege']['authorisation']=='authorized') {
                    $doc->body['wilayah_penugasan']=$this->val['privilege']['wilayah_nama'];
                } elseif ($this->val['privilege']['authorisation']=='unauthorized' && $this->val['userRole']=='member')  {
                  throw new \Exception("Unauthorized:Akun Anda tidak memiliki wewenang di ".ucfirst($this->val['privilege']['level_label'])." ".$this->val['privilege']['nama'].", silakan hubungi Admin");   
                }
            } else {
                $this->val['privilege']['authorisation']='authorized';
                $this->val['privilege']['wilayah_id']=$wilayah_id;
//                $this->sesSave($_gov2session);
                $this->sesSave($this->val);
            }
        } catch (\Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }
//        $this->authorized=$this->val;
    }
    
    function privilegeRead ($wilayah_id) { 
        global $doc,$config,$self;
        try {
            $_query="SELECT * FROM wilayah WHERE id=%i";
            $_wilayah=\DB::queryFirstRow($_query,$wilayah_id);
            #otorisasi level kecamatan kebawah
            if ($_wilayah['level']==3) {
                $_kec_id=$wilayah_id;
            } elseif ($_wilayah['level']==4) {
                $_kec_id=$_wilayah['parent_id'];
            } else {
                $_kec_id=0;
            }
            if ($_kec_id>0) {
                $_query="SELECT * FROM privilege WHERE member_id=%i AND kecamatan_id=%i";
                $_result=\DB::queryFirstRow($_query,$self->ses->val['id'],$_kec_id);
                if ($_result['id']) {
                    $_result['authorisation']='authorized';    
                } else {
                    $_result=$_wilayah;
                    $_result['authorisation']='unauthorized';
                }
            } else {
                $_result=$_wilayah;
                $_result['authorisation']='authorized';
            }
        } catch (\MeekroDBException $e) {
			$doc->exceptionHandler($e->getMessage());
		}
	return $_result;
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
