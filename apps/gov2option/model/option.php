<?php namespace App\gov2option\model;

class option extends \Gov2lib\crudHandler {
	function __construct () {
        global $doc,$config;
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $doc->body("className",$this->className);
        parent::__construct($config->domain->attr['dsn']);
        $this->tbl->table=$this->tbl->options;
	}
    
    function loadTable ($_scrollInterval) {
        global $config,$pageID;
        //---gov2pagination
        $GLOBALS['vueData']['pathurl']=$config->webroot."/components/gov2nav/breadcrumb/$pageID/".$this->className."/menu";
        $GLOBALS['vueData']['kabid']=$config->domain->attr['id'];
        $GLOBALS['vueData']['itemPerPage']=100;
        $GLOBALS['vueCreated'].='eventBus.$on("refreshTag", this.refreshTag);';
        $GLOBALS['vueMethods'].='refreshTag: function(data) {            
			eventBus.$emit("refreshDatawilayah",data);
		},';
	}

	function insert($data) {
	    global $uri;
	    $_id = 0;
	    try {
	        \DB::insert($this->tbl->table, $data);
	        $_id = \DB::insertId();
        } catch (\MeekroDBException $e) {
	        $this->exceptionHandler($e->getMessage().":".$uri);
        } catch (\Exception $e) {
            $this->exceptionHandler($e->getMessage().":".$uri);
        }
        return $_id;
    }

    function getOptions($id, $type='option') {
        global $uri, $scriptID;
        $id = intval($id);
        $ORDER = "";
        if ($id > 0) {
            $WHERE = "WHERE parent_id={$id}";
            $ORDER = "ORDER BY id ASC";
        } else {
            if ($type === 'service') {
                $WHERE = "WHERE level=1 and type='service'";
            } else {
                $WHERE = "WHERE app='{$scriptID}' ";
                $WHERE .= "AND type='{$type}' AND level=1";
            }
        }

        $q = "SELECT id, app, type, privilege, nama, keterangan, status, value FROM ".$this->tbl->table." $WHERE {$ORDER}";
        $results = [];

        try {
            $results = \DB::query($q);
        } catch (\Exception $e) {
            $this->exceptionHandler($e->getMessage());
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().":".$uri);
        }
        return $results;
    }

    function saveItems($data) {
	    foreach ($data as $row) {
	        try {
                \DB::update($this->tbl->options, $row, 'id=%i', $row['id']);
            } catch (\MeekroDBException $e) {
	            $this->exceptionHandler($e->getMessage());
            }
        }
    }

    function postAdd ($_data) {
        global $doc,$config,$scriptID,$requester;
        $errors=$this->gov2formfield->checkRequired($_data,$this->fields);
        if (is_array($errors)) {
            $response=$errors;
            if ($config->css->warning) {
                $response["class"]=(string) $config->css->warning;
            } else {
                $response["class"]="warning";
            }
            $response["notification"]="Harap isi form dengan lengkap";
            header("HTTP/1.1 422 Incomplete fields");
        } else {
            $id=$this->insert($_data);

            if (!is_array($doc->error)) {
                $data=$this->doRead($id);
                $response=$doc->response("primary","resetButton",$data['id']);
            } else {
                $response=$doc->response("danger","resetButton");
                if ($requester=='browser') {
                    header("HTTP/1.1 422 Query Fails");
                }
            }
        }
        return $response;
    }

    function getRecords ($vars,$parent_name="") {
        global $doc,$self;
        $_id=$this->setRememberId($vars['id']);
        $data=$this->doBrowse($vars['scroll'],$_id,$parent_name);
        if (sizeof($data)==0) {$data=array("data"=>"empty","level"=>"1");}
        return $doc->responseGet($data);
    }

    function doBrowse ($scroll,$parent_id="",$parent_id_name="") {
        global $uri, $scriptID;
        $WHERE = "WHERE app='{$scriptID}' ";
        try {
            $scrolled=$this->scroll($scroll);
            if ($parent_id_name) {$_parent=$parent_id_name."_id";}
            else {$_parent="parent_id";}
            if (isset($parent_id)) {$WHERE.="AND $_parent=%i";}
            $query="SELECT * FROM ".$this->tbl->table." $WHERE LIMIT $scrolled";
            // echo \DB::$dbName;
            // echo \DB::$host;
            $results = \DB::query($query,$parent_id);
        } catch (\Exception $e) {
            $this->exceptionHandler($e->getMessage());
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage().":".$uri);
        }
        return $results;
    }

    function getExpiry($cluster_id) {
	    $q = "SELECT value as exp FROM {$this->tbl->table} WHERE nama='exp' and parent_id=%i";
	    $res = ['exp' => false];
	    try {
	        $res = \DB::queryFirstRow($q, $cluster_id);
	        if ($res) {
                $res['expired'] = new \DateTime($res['exp']) < new \DateTime();
            }
        } catch (\MeekroDBException $e) {
	        $this->exceptionHandler($e->getMessage());
        }
        return $res;
    }

    function service_del($cluster_id) {
	    $q = "DELETE FROM {$this->tbl->table} WHERE id=%i OR parent_id=%i";
	    $res = 0;
	    try {
	        \DB::queryFirstRow($q, $cluster_id, $cluster_id);
	        $res = \DB::affectedRows();
        } catch (\MeekroDBException $e) {
	        $this->exceptionHandler($e->getMessage());
        }
        return $res;
    }
}
?>