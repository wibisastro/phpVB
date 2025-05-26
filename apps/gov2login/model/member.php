<?php namespace App\gov2login\model;

class member extends \Gov2lib\crudHandler {
	function __construct ($dsn = "") {
        global $doc,$config;
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $doc->body("className",$this->className);
//        parent::__construct($config->domain->attr['dsn']);
        if (!$dsn) {
            try {
                $cookies = $doc->envRead($_COOKIE['Gov2Session']);
                $dsn = $cookies['portal'];
                $this->predefined_dsn = $dsn;
                $this->predefined_dsn_id = $cookies['portal_id'];
                if (!$dsn) {
                    $dsn = $config->domain->attr['dsn'];
                }
            } catch (\Exception $e) {
                $dsn = $config->domain->attr['dsn'];
            }
        }
        parent::__construct($dsn);
        $this->tbl->table=$this->tbl->member;
        $this->tbl->wilayah=$this->tbl->wilayah;
        $this->tbl->kementerian=$this->tbl->kementerian;
        $this->tbl->source = $this->tbl->kementerian;
        $this->tbl->target = $this->tbl->member;
	}
    
    function loadTable ($_scrollInterval) {
        //---gov2pagination
        $GLOBALS['vueData']['itemPerPage']=20;
        $GLOBALS['vueData']['scrollInterval']=$_scrollInterval;
        $GLOBALS['vueData']['interval']=array(50,100);
        
        //---gov2formfield
        $GLOBALS['vueData']['readOnly']=false;
        $GLOBALS['vueData']['fieldurl']=$this->className.'/fields'; //<-overwrite default
	}

    function browse ($scroll)
    {
        global $uri, $config;
        $scrolled=$this->scroll($scroll);
        $result = [];
        $conn =  new \Gov2lib\DBConnector($config->domain->attr['dsn']);
        $query = "SELECT * FROM {$this->tbl->table} ORDER BY id DESC LIMIT $scrolled";
        try {
            $result = $conn->db->query($query);
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage().":".$uri);
		} catch (\Exception $e) {
			$this->exceptionHandler($e->getMessage().":".$uri);
		}
	    return $result;
    }
    
    function memberBrowse ($role,$scroll) {
        global $uri;

        $scrolled=$this->scroll($scroll);
        $query="SELECT * FROM {$this->tbl->table} WHERE role=%s LIMIT $scrolled";

        // if ($role === 'member') {
        //     $query="SELECT * FROM {$this->tbl->table}";
        // }

        try {
            // $results = \DB::query($query,$this->memberWhere());
            $_results = \DB::query($query,$role);
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage().":".$uri);
		}
	    return $_results;
	}
    
    function memberCount ($role) {
		$query="SELECT count(id) as totalRecord FROM ".$this->tbl->table." WHERE role=%s";	
	    return \DB::queryFirstRow($query,$role);
	}
    /*
    function memberWhere () {
        $_where = new \WhereClause('and');
        $_where->add('role!=%s', 'owner');
        $_where->add('role!=%s', 'developer');
        return $_where;
    }
    */
    function roleBrowse ($superUser) {
        global $self, $uri;
//        $role_level = ['guest' => 1, 'member' => 2, 'komisioner' => 3, 'admin' => 4, 'webmaster' => 5];
        // $role_level = ['guest' => 1, 'member' => 2, 'pimpinan' => 3, 'admin' => 4, 'webmaster' => 5];
        $role_level = ['guest' => 1, 'member' => 2, 'admin' => 3, 'webmaster' => 4];
        $current_level = $role_level[$self->ses->val['userRole']];
        try {
            $_member = \DB::query("DESCRIBE member");
            foreach ($_member as $_column) {
                if ($_column['Field']=="role") {
                    $_result=str_replace("enum(","",$_column['Type']);
                    $_result=str_replace(")","",$_result);
                    $_result=str_replace("'","",$_result);
                    $_result=explode(",",$_result);
                    break;
                }
            }
            $_buffer=array_flip($_result);
            foreach ($_result as $_key => $_val) {
//                if ($_key <= $_buffer[$self->ses->val['userRole']]) {
                if($superUser){
                    if (isset($role_level[$_val]) && ($current_level >= $role_level[$_val])) {
                        $_reorder[$_key+1]=$_val;
                    }
                }else{
                    if (isset($role_level[$_val]) && ($current_level > $role_level[$_val])) {
                        $_reorder[$_key+1]=$_val;
                    }
                }
//                else {
//                    break;
//                }
            }

            $_result=$_reorder;
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage().":".$uri);
		}
        return $_result;
    }
    
    /*
    function memberWhere () {
        $_where = new \WhereClause('and');
        $_where->add('role!=%s', 'owner');
        $_where->add('role!=%s', 'developer');
        return $_where;
    }
    */

    function getCurrentUser ($id) {
        global $uri;
        try {
            $query="SELECT id,account_id,role FROM {$this->tbl->table} WHERE account_id=%i LIMIT 1";
            $result = \DB::query($query,$id);
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage().":".$uri);
		}
	    return $result;
    }

    function getBrowseTags ($_id,$_source,$_target,$_target2="",$_caption="")
    {
        global $doc,$config,$self;
        $_id=$this->setRememberId($_id,$_source);
        $data=$this->doBrowseTags($_id,$_source,$_target,$_target2,$_caption);
        if (sizeof($data)==0) {$data=array("data"=>"empty");}
        return $doc->responseGet($data);        
    }

    function doBrowseTags ($source_id,$_source,$_target,$_target2="",$_caption="")
    {
        global $config, $vars;
        $role = $_GET['role'];
        $connector = new \Gov2lib\DBConnector($config->domain->attr['dsn']);

        try {
            $query="SELECT *,
            ".$_target."_".$_caption." AS target_" .$_caption.",
            ".$_target."_id AS target_id,
            ".$_source."_id AS source_id";

            $query.=" FROM ".$this->tbl->role." WHERE ".$_source."_parent=%i AND member_role=%s";
            
            $results = $connector->db->query($query,$source_id, $role);
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler('doBrowseTags:'.$e->getMessage());
		}
	    return $results;
	}

    function postTagging ($_data, $_source, $_target, $_target2="", $_caption="")
    {
        global $doc, $config, $self;
        if (!$_data['source_id'] || !$_data['target_id']) {
            $response["class"] = $config->css->attr['is-warning'];
            $response["notification"] = "Pasangan ID tidak lengkap";
            header("HTTP/1.1 422 Incomplete fields");
        } else {
            $id=$this->doTagging($_data, $_source, $_target, $_target2, $_caption);
            if (!is_array($doc->error)) {

                // set user insert/update on unit
                $this->set_user_unit($id);

                $response = $doc->response("is-primary", "", $id);
            } else {
                $response["class"] = "is-danger";
                $response["notification"] = $doc->error;
                $response["callback"] = "infoSnackbar";
                header("HTTP/1.1 422 Insert Fails");
            }
        }
        return $response;
    }

    function doTagging ($data, $_source, $_target, $_target2, $_caption)
    {
        global $uri, $self, $vars, $config;
        $role = $_GET['role'];
        $account_id = $self->ses->val['account_id'];
        $connector = new \Gov2lib\DBConnector($config->domain->attr['dsn']);

        try {
            $query="SELECT * FROM ".$this->tbl->role." WHERE ".$_source."_id=%i AND ".$_target."_id=%i";
            $_tagged=$connector->db->queryFirstRow($query, $data['source_id'], $data['target_id']);

            if ($_tagged['id']) {
                if ($_tagged['member_role'] !== $role) {
                    $_tagged['member_role'] = $role;
                    $this->update_tag($_tagged);
                    return $_tagged['id'];
                } else {
                    throw new \Exception('AlreadyTagged: '.$_tagged[$_target.'_'.$_caption].' source_id='.$data['source_id']);
                }
            } else {
                $query="SELECT * FROM ".$this->tbl->source." WHERE id=%i";

                $_sourceData=$connector->db->queryFirstRow($query, $data['source_id']);
                $query="SELECT * FROM ".$this->tbl->target." WHERE id=%i";
                $_targetData=$connector->db->queryFirstRow($query, $data['target_id']);

                $_insert[$_source.'_id'] = $data['source_id'];
                $_insert[$_source.'_parent'] = $_sourceData['parent_id'];
                $_insert[$_source.'_nama'] = $_sourceData['nama'];

                $_insert[$_target.'_id'] = $data['target_id'];
                $_insert[$_target.'_email'] = $_targetData['email'];
                $_insert[$_target.'_account_id'] = $_targetData['account_id'];
                $_insert[$_target.'_role'] = $_GET['role'];

                // fullname
                $_insert[$_target.'_'.$_caption] = $_targetData[$_caption];

                $_insert['created_by'] = $account_id ? $account_id : 0;

                $columns = $connector->db->columnList($this->tbl->role);
                if (in_array($_target.'_level', $columns)) {
                    $_insert[$_target.'_level']=$_targetData['level_label'];
                }

                $connector->db->insert($this->tbl->role, $_insert);
                $_id = $connector->db->insertId();
                return $_id;
            }
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler('doTagging:'.$e->getMessage());
        } catch (\Exception $e) {
            $this->exceptionHandler('doTagging:'.$e->getMessage());
        }
    }

    function postDelTag ($_data)
    {
        global $doc;
        if (!$_data['id']) {
            $response['id']='No ID number';
            header("HTTP/1.1 422 Incomplete fields");
        } else {

            $tag = $this->get_tag($_data['id']);

            $affected = $this->delTag($_data['id']);

            if ($affected) {
                $data = [
                    'account_id' => $tag['member_account_id'],
                    'unit_id' => $tag['unit_id']
                ];

                $user = [
                    'unit_id' => $tag['unit_id'],
                    'account_id' => $tag['member_account_id'],
                    'fullname' => $tag['member_fullname'],
                    'email' => $tag['member_email'],
                    'role' => 'guest',
                ];

                // change user role on unit to guest
                $this->update_user_unit($user);

                // insert/update to db default as guest
                $user_exists = $this->get_user(['account_id' => $user['account_id']]);
                if ($user_exists) {
                    $this->update_user($user);
                } else {
                    $this->add_user($user);
                }

                // $this->delete_user_unit($data);
            }
            
            $response=$doc->response("is-primary");
        }
        return $response;
    }

    function delTag($_id=0) 
    {
        global $uri, $config;
        $connector = new \Gov2lib\DBConnector($config->domain->attr['dsn']);
        $affected = 0;

        try {
            // $data = $this->get_tag($_id);
            $connector->db->delete($this->tbl->role, "id=%i", $_id); 
            $affected = $connector->db->affectedRows();
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage());
		}
        return $affected;
	}

    function add_user (&$data)
    {
        global $uri, $config;
        $connector = new \Gov2lib\DBConnector($config->domain->attr['dsn']);
        $affected = 0;

        $user = [
            'account_id' => $data['account_id'],
            'fullname' => $data['fullname'],
            'email' => $data['email'],
            'status' => 'active',
            'role' => $data['role'],
            'kab_id' => 0,
            'prov_id' => 0
        ];

        try {
            $connector->db->insert($this->tbl->member, $user);
            $affected = $connector->db->affectedRows();
            $id = $connector->db->insertId();
            $data = $this->get_user($id);
        } catch(\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        } catch(\Exception $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $affected;
    }

    function add_user_unit (&$data)
    {
        global $uri, $config;
        $unit_kerja = $this->get_unitkerja($data['unit_id']);
        $affected = 0;

        $user = [
            'account_id' => $data['account_id'],
            'fullname' => $data['fullname'],
            'email' => $data['email'],
            'status' => 'active',
            'role' => $data['role'],
            'kab_id' => 0,
            'prov_id' => 0
        ];

        if ($unit_kerja) {
            $connector = new \Gov2lib\DBConnector($unit_kerja['portal']);
        }

        try {
            $connector->db->insert($this->tbl->member, $user);
            $affected = $connector->db->affectedRows();
            $data = $this->get_user_unit($user['account_id'], $unit_kerja['portal']);
        } catch(\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        } catch(\Exception $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $affected;
    }

    function update_user (&$data)
    {
        global $uri, $config;
        $connector = new \Gov2lib\DBConnector($config->domain->attr['dsn']);
        $affected = 0;
        $status = isset($_POST['status']) ? $_POST['status'] : 'active';

        $user = [
            'account_id' => $data['account_id'],
            'fullname' => $data['fullname'],
            'email' => $data['email'],
            'status' => $status,
            'role' => $data['role'],
            'kab_id' => 0,
            'prov_id' => 0
        ];
        
        try {
            $connector->db->update($this->tbl->member, $user, "account_id=%s", $user['account_id']);
            $affected += $connector->db->affectedRows();
        } catch(\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        } catch(\Exception $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $affected;
    }

    function update_user_unit (&$data)
    {
        global $uri, $config;
        $unit_kerja = $this->get_unitkerja($data['unit_id']);

        $user = [
            'account_id' => $data['account_id'],
            'fullname' => $data['fullname'],
            'email' => $data['email'],
            'status' => 'active',
            'role' => $data['role'],
            'kab_id' => 0,
            'prov_id' => 0
        ];
        
        if ($unit_kerja) {
            $connector = new \Gov2lib\DBConnector($unit_kerja['portal']);
        }
        
        try {
            $connector->db->update($this->tbl->member, $user, "account_id=%s", $user['account_id']);
        } catch(\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        } catch(\Exception $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
    }

    function update_tag (&$data)
    {
        global $uri, $config;
        $connector = new \Gov2lib\DBConnector($config->domain->attr['dsn']);
        
        try {
            $connector->db->update($this->tbl->role, $data, "id=%i", $data['id']);
            $this->set_user_unit($data['id']);
        } catch(\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        } catch(\Exception $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
    }

    function delete_user ($id)
    {
        global $uri, $config;
        $affected = 0;

        $connector = new \Gov2lib\DBConnector($config->domain-attr['dsn']);
        
        try {
            $connector->db->delete($this->tbl->member, 'id=%i', $id);
            $affected += $connector->db->affectedRows();
        } catch(\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        } catch(\Exception $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $affected;
    }

    function delete_user_unit (&$data)
    {
        global $uri, $config;
        $affected = 0;
        $unit_kerja = $this->get_unitkerja($data['unit_id']);

        if ($unit_kerja) {
            $connector = new \Gov2lib\DBConnector($unit_kerja['portal']);
        }

        try {
            $connector->db->delete($this->tbl->member, 'account_id=%s', $data['account_id']);
            $affected = $connector->db->affectedRows();
        } catch(\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        } catch(\Exception $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $affected;
    }

    function set_user_unit ($tag_id) 
    {
        global $uri, $config;

        $tag = $this->get_tag($tag_id);
        $user = [
            'account_id' => $tag['member_account_id'],
            'fullname' => $tag['member_fullname'],
            'email' => $tag['member_email'],
            'status' => 'active',
            'role' => $tag['member_role'],
            'unit_id' => $tag['unit_id']
        ];
        $unit = $this->get_unitkerja($tag['unit_id']);
        $user_unit_exists = $this->get_user_unit($user['account_id'], $unit['portal']);

        if ($user_unit_exists) {
            $this->update_user_unit($user);
        } else {
            $this->add_user_unit($user);
        }

        return $user;
    }

    function get_user ($id)
    {
        global $uri, $config;
        $result = null;
        $connector = new \Gov2lib\DBConnector($config->domain->attr['dsn']);
        $where_clause = new \WhereClause('and');

        $q = "SELECT * FROM {$this->tbl->member} WHERE %l";

        if (is_array($id)) {
            foreach ($id as $key => $val) {
                $kwarg = gettype($val) === 'string' ? "{$key}=%s" : "{$key}=%i";
                $where_clause->add($kwarg, $val);
            }
        } else {
            $where_clause->add('id=%i', $id);
        }

        try {
            $result = $connector->db->queryFirstRow($q, $where_clause);
        } catch(\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        } catch(\Exception $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }

    function get_user_unit ($account_id, $dsn)
    {
        global $uri, $config;
        $result = null;
        $connector = new \Gov2lib\DBConnector($dsn);
        $q = "SELECT * FROM {$this->tbl->member} WHERE account_id=%i";

        try {
            $result = $connector->db->queryFirstRow($q, $account_id);
        } catch(\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        } catch(\Exception $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }

    function get_tag ($id)
    {
        global $uri, $config;
        $result = null;
        $connector = new \Gov2lib\DBConnector($config->domain->attr['dsn']);
        $q = "SELECT * FROM {$this->tbl->role} WHERE id=%i";

        try {
            $result = $connector->db->queryFirstRow($q, $id);
        } catch(\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        } catch(\Exception $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }

    function get_unitkerja ($id)
    {
        global $uri, $config;
        $result = null;
        $q = "SELECT * FROM {$this->tbl->ref_unitkerja} WHERE id=%i";

        try {
            $result = \DB::queryFirstRow($q, $id);
        } catch(\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        } catch(\Exception $e) {
            $this->exceptionHandler($e->getMessage().':'.$uri);
        }
        return $result;
    }

    function get_xml ($filename) 
    {
        $_filePath = __DIR__."/../xml/{$filename}.xml";
        $_pageroles = simplexml_load_file($_filePath, "SimpleXMLElement", LIBXML_NOCDATA);

        if ($_pageroles->share) {
            $shared_file = __DIR__."/../../apps/{$_pageroles->share}/xml/{$file}.xml";

            if (file_exists($shared_file)) {
                $shared_file_list = simplexml_load_file($shared_file, "SimpleXMLElement", LIBXML_NOCDATA);

                if (is_object($shared_file_list)) {
                    $_pageroles = $shared_file_list;
                } else {
                    throw new \Exception('InvalidPagerolesShareFile:' . $shared_file);
                }
            }
        }
        return $_pageroles;
    }
}
?>