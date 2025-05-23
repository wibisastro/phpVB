<?php namespace Gov2lib;
/*
Author		: Wibisono Sastrodiwiryo
Date		: 21 Dec 2017
Copyleft	: eGov Lab UI
Contact		: wibi@alumni.ui.ac.id
Version		: 0.0.1
Version		: 0.0.2 nambah error message role 6 Aug 2020
Version		: 0.0.3 nambah propagasi superadmin per wilayah 10 Aug 2020
Version		: 0.0.4 05 April 2021, [rijal@cybergl.co.id] fix bug salah query field, yg tadinya ke field account_id menjadi ke field id, @line 198
Version		: 0.0.5 06 Mei 2021, [rijal@cybergl.co.id] ganti superadmin menjadi superuser dan tambah fungsi share xml
Version		: 0.0.6 26 Mei 2021, [rijal@cybergl.co.id] ubah str_replage menjadi preg_replace @line 75
*/
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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
                // $this->sesSave($_token,1);
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
            $_token = JWT::encode($_data, $publickey, 'HS256');
        }
        //$_existing = \Firebase\JWT\JWT::decode($_token, $publickey, array('HS256'));
        $_existing = JWT::decode($_token, new Key($publickey, 'HS256'));
        $_data = array_merge((array)$_existing,(array)$data);
        $_token = JWT::encode($_data, $publickey, 'HS256'); 
        
        setcookie("Gov2Session", $_token, $this->timeout,"/");
        if ($redirect) { 
            header('location: /');
            exit;
        }
    }
    
    function sesRead ($data) {
        global $publickey;
         //$_result=\Firebase\JWT\JWT::decode($data, $publickey, array('HS256'));
        $_result= JWT::decode($data, new Key($publickey, 'HS256'));
        $this->val=json_decode(json_encode($_result), true);
        // $GLOBALS['_GOV2SES']=(array)$_result;
    }
    
    function getRoleLevel ($table,$levelName) {
        $_member = \DB::query("DESCRIBE $table");
        $patterns = array("/^enum\(/x", "/'/x", "/\)/x");
        foreach ($_member as $_column) {
            if ($_column['Field']==$levelName) {
                $_result = preg_replace($patterns, "", $_column['Type']);
//                $_result=str_replace("enum(","",$_column['Type']);
//                $_result=str_replace(")","",$_result);
//                $_result=str_replace("'","",$_result);
                $_result=explode(",",$_result);
                break;
            }
        }
        foreach ($_result as $_key => $_val) {
            $_reorder[$_key+1]=$_val;
        }
        $_result=array_flip($_reorder);
        return $_result;
    }
    
    function authenticate ($_privilege="member", $_maintenance="") {
        global $pageID,$doc,$config;

        // if ($pageID != 'apidpsnapshot') {
        //     header('Location: /maintenance.html');
        //     exit;
        // }
        
        try {
            if ($_privilege=="Selesai") {
                throw new \Exception("Selesai: Semua fungsi ditutup karena proses kerja telah selesai");
            } elseif (STAGE!='dev') {
				if ($config->domain->attr['shift'] 
                        && $config->domain->attr['shift'] != date("A") 
                        && $config->domain->attr['shift']!='ALL') {
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
                            if ($pageID!="gov2login") {$_gov2session['pageID']=$pageID;}
                            $this->sesSave($_gov2session,1);
                        }
                    } else {
                        //-fix ketika setelah login, terdeteksi sbg public di index page yg authenticate 'public'.
                        //-Penyebabnya ada di line 49 dan authenticate('public') pada index page. Jadi dia tidak
                        //-masuk ke line 108.
                        if (isset($this->val['account_id']) && !isset($this->val['id'])) {
                            $_member = $this->memberRead($this->val['account_id']);
                            if ($_member['id']) {
                                $_gov2session['id']=$_member['id'];
                                $_gov2session['userRole']=$_member['role'];
                                $_gov2session['status']=$_member['status'];
                                if ($pageID!="gov2login") {$_gov2session['pageID']=$pageID;}
                                $this->sesSave($_gov2session, 1);
                            }
                        }

                        $doc->body['_SESSION["userRole"]']=$this->val['userRole'];
                        $this->memberUpdateCounter($this->val['id']);
                        
                        #--------------tambahan untuk log activity
                        //$this->memberLog($this->val['id']);

                        switch ($this->val['status']) {
                            case "pending":
                                throw new \Exception("Pending:Akun Anda belum aktif, silahkan aktivasi terlebih dahulu");
                            break;
                            case "suspended":
                                throw new \Exception("Suspended:Akun Anda terblokir, silakan hubungi Admin");
                            break;
                            default:
                                 $_userRole=$this->getRoleLevel('member','role');
//                                $_userRole = ['guest' => 1, 'pimpinan' => 2, 'member' => 3, 'admin' => 4, 'webmaster' => 5];
                                $_customPageroles=$this->readXML($pageID,"pageroles");

                                if ($_customPageroles) {$_pageRole=(array) $_customPageroles;} 
                                else {$_pageRole=(array) $config->pageroles;}

                                $_role=$this->checkSuperuser();
                                if ($_role)  {$this->val['userRole']=$_role;}
                                if ($_userRole[$this->val['userRole']]<$_pageRole[$_privilege] && $_privilege!='closed' && $_privilege!='maintenance') {
                                    throw new \Exception("Unauthorized:UserRole akun Anda tidak memiliki wewenang mengakses halaman dengan PageRole ".strtoupper($_privilege).". Silakan hubungi Admin");
                                } elseif ($_userRole[$this->val['userRole']]<$_pageRole[$_privilege] && $_privilege=='closed') {
                                    throw new \Exception("Closed:Menu ini ditutup");
                                } elseif ($_privilege=='maintenance') {
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
        // $this->authorized=$_gov2session;
    }
    
    function memberUpdateCounter($id) {
        try {
            $_query="UPDATE LOW_PRIORITY member SET counter=counter+1,lastlogin_at=NOW() WHERE id=%i";
            \DB::query($_query,$id);
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage());
		}
	}
    
    function memberRead ($id=0) {
        global $doc;
        $WHERE = "account_id=%i";
        if (gettype($id) === 'string') {
            $WHERE = "account_id=%s";
        }

        try {
            $_role=$this->checkSuperuser();
            $_query="SELECT * FROM member WHERE {$WHERE}";
            $_result=\DB::queryFirstRow($_query,$id);
            if (!is_array($_result)) {
                /** @var $_id int id dari row di table member $_query */
                $_id=$this->insertMember();
//                $_query="SELECT * FROM member WHERE account_id=%i";
                /** @var  $_query saya ubah account_id menjadi id, karena $_id bukan account_id, tp id */
                $_query="SELECT * FROM member WHERE id=%i";
                $_result=\DB::queryFirstRow($_query,$_id);
            }
            if ($_role) {$_result['role']=$_role;}
        } catch (\MeekroDBException $e) {
			$doc->exceptionHandler($e->getMessage());
		}
	return $_result;
	}
    
    function insertMember () {
        global $doc,$config,$pageID;
        $_role="guest";
        try {
            $_customDataroles=$this->readXML($pageID,"dataroles");
            if ($_customDataroles) {$_dataRole=$_customDataroles;} 
            else {$_dataRole=(array) $config->dataroles;}

            $_level1=$_dataRole["level"][0];
            $_level2=$_dataRole["level"][1];
            if ($config->domain->attr['level']==0) {            
                ${"_".$_level1."_id"}=0;
                ${"_".$_level2."_id"}=0;
            }
            else if ($config->domain->attr['level']==1) {
                ${"_".$_level2."_id"}=0;
                ${"_".$_level1."_id"}=trim($config->domain->attr['id']);
            } else {
                ${"_".$_level2."_id"}=trim($config->domain->attr['id']);
                ${"_".$_level1."_id"}=$this->parentRead(trim($config->domain->attr['table']),trim($config->domain->attr['id']));
                ${"_".$_level1."_id"}+=0;
            }
            $_fields=array('account_id' => $this->val['account_id'],
                'fullname' => $this->val['fullname'],
                'email' => $this->val['email'],
                'status' => "active",
                'role' => $_role,
                'counter' => "1",
                'lastlogin_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                $_level2.'_id' => ${"_".$_level2."_id"},
                $_level1.'_id' => ${"_".$_level1."_id"} 
             );
            // print_r($_fields);
            \DB::insert("member", $_fields);
            return \DB::insertId();
            
        } catch (\MeekroDBException $e) {
			$doc->exceptionHandler($e->getMessage());
		} catch (\Exception $e) {
			$doc->exceptionHandler($e->getMessage());
		}
	}
    
    function checkSuperuser ($newPageID = null) {
        global $pageID,$config;
        $_role="";
        
        $getPageID = $pageID;
        if($newPageID){
            $getPageID = $newPageID;
        }

        $_superuser=$this->readXML($getPageID,"superuser");
        if ($config->domain->attr['level']==2) {
            $_id=$this->parentRead(trim($config->domain->attr['table']), trim($config->domain->attr['id']));
        } else {
            $_id=trim($config->domain->attr['id'] ?? '');
        }
        
        foreach ($_superuser->role as $_roles) {
            $_attr=$_roles->attributes();
            
            if (in_array($this->val['account_id'], (array)$_roles->account_id) && !$_attr['id']) {
                $_role=true;
            } else if (in_array($this->val['account_id'], (array)$_roles->account_id) && $_attr['id']>0 && $_id==$_attr['id']) {
                $_role=true;
            } else {
                $_role=false;
            }
            
            if ($_role==true) {
                $_role=trim(str_replace("'","",stripslashes($_attr['name'])));
                break;
            }
        }
        
        return $_role;
    }
    
    function parentRead ($table="",$id=0) {
        global $doc;
        if (!$table) { 
            $table="wilayah_local";
        }
        $_query="SELECT * FROM $table WHERE id=%i";
        try {
            $result=\DB::queryFirstRow($_query,$id);
        } catch (\MeekroDBException $e) {
        	$doc->exceptionHandler($e->getMessage());
		}
        return $result['parent_id'];
	}
    
    function authorize ($id,$structure="wilayah",$role="member",$level=3) {
        global $pageID,$doc;
        
		if (!$structure) {$structure="wilayah";}

        try {
            if (STAGE!='dev') {
                $_userRole=$this->getRoleLevel('member','role');
                $_privilege=$this->privilegeRead($id,$structure,$level);
                
                if (!$this->val['privilege']) {
                    $this->val['privilege']=$_privilege;
                    $this->sesSave($this->val);
                } elseif ($this->val['privilege'][$structure.'_id']!=$id) {
                    unset($this->val['privilege']);
                    $this->val['privilege']=$_privilege;
                    $this->sesSave($this->val);
                }
                
                if ($this->val['privilege']['authorisation']=='authorized') {
                    $doc->body[$structure.'_penugasan']=$this->val['privilege'][$structure.'_nama'];
                } elseif (
                    $this->val['privilege']['authorisation']=='unauthorized' && 
                    $_userRole[$this->val['userRole']]<=$_userRole[$role])  {
                  throw new \Exception("Unauthorized:Akun Anda tidak memiliki wewenang di ".ucfirst($this->val['privilege']['level_label'])." ".$this->val['privilege']['nama'].", silakan hubungi Admin");   
                }
            } else {
                $this->val['privilege']['authorisation']='authorized';
                $this->val['privilege'][$structure.'_id']=$id;
                $this->sesSave($this->val);
            }
        } catch (\Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }
    }
    
    function privilegeRead ($id,$structure,$level=3) { 
        global $doc,$config,$self;
        try {
            $_query="SELECT * FROM $structure WHERE id=%i";
            $_structure=\DB::queryFirstRow($_query,$id);
            
            $_levelRole=array_flip($this->getRoleLevel($structure,'level_label'));
            
            $_id=$_structure[$_levelRole[$level]."_id"];
            if ($_id>0) {
                $_query="SELECT * FROM privilege WHERE member_id=%i AND ";
                $_query.=$_levelRole[$level]."_id=%i";
                
                $_result=\DB::queryFirstRow($_query,$self->ses->val['id'],$_id);
                if ($_result['id']) {
                    $_result['authorisation']='authorized';    
                } else {
                    $_result=$_structure;
                    $_result['authorisation']='unauthorized';
                }
            } else {
                $_result=$_structure;
                $_result['authorisation']='authorized';
            }
        } catch (\MeekroDBException $e) {
			$doc->exceptionHandler($e->getMessage());
		}
	return $_result;
	}
    
    function readXML ($pageID,$file) {
        global $doc;
        $_data = null;
        if ($this->val['pageID']) {
            $_pageID=$this->val['pageID'];
        } else {
            $_pageID=$pageID;
        }
        $_filePath=__DIR__."/../../apps/$_pageID/xml/$file.xml";
        if (file_exists($_filePath)) {
            $_data = simplexml_load_file($_filePath, "SimpleXMLElement", LIBXML_NOCDATA);

            if (is_object($_data)) {
                if ($_data->share) {
                    $shared_file = __DIR__."/../../apps/{$_data->share}/xml/{$file}.xml";
                    if (file_exists($shared_file)) {
                        $shared_file_list = simplexml_load_file($shared_file,
                            "SimpleXMLElement", LIBXML_NOCDATA);
                        if (is_object($shared_file_list)) {
                            $_data = $shared_file_list;
                        } else {
                            throw new \Exception('InvalidSuperuserShareFile:' . $shared_file);
                        }
                    } else {
                        throw new \Exception('SuperUserShareFileNotExist:' . $shared_file);
                    }
                }
            }
        }
        return $_data;
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
        // try {
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
                    // $doc->error("ErrToken",$e->getMessage());
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
    // }
}
?>
