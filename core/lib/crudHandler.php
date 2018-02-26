<?php namespace Gov2lib;
    
class crudHandler extends crudModel {
	function __construct ($_dsn="") {
        parent::__construct($_dsn);  
	}
    
    function postDelTag ($_data) {
        global $doc;
        if (!$_data['id']) {
            $response['id']='No ID number';
            header("HTTP/1.1 422 Incomplete fields");
        } else {
            $this->doDel($_data['id']);
            $response=$doc->response("is-primary");
        }
        return $response;
    }
    
    function getBrowseTags ($_id,$_source,$_target,$_target2="") {
        global $doc;
        $_id=$this->setRememberId($_id,$_source);
        $data=$this->doBrowseTags($_id,$_source,$_target,$_target2);
        if (sizeof($data)==0) {$data=array("data"=>"empty");}
        return $doc->responseGet($data);        
    }
    
    function postTagging ($_data,$_source,$_target,$_target2="") {
        global $doc;
        if (!$_data['source_id'] || !$_data['target_id']) {
            $response["class"]="is-warning";
            $response["notification"]="Pasangan ID tidak lengkap";
            header("HTTP/1.1 422 Incomplete fields");                    
        } else {
            $id=$this->doTagging($_data,$_source,$_target,$_target2);
            if (!is_array($doc->error)) {
                $data=$this->doRead($id);
                $response=$doc->response("is-primary","",$data['id']);
            } else {
                $response=$doc->response("is-danger","");
                header("HTTP/1.1 422 Query Fails");
            }
        }
        return $response;
    }

    function postAdd ($_data) {
        global $doc;
        $errors=$this->gov2formfield->checkRequired($_data,$this->fields);
        if (is_array($errors)) {
            $response=$errors;
            $response["class"]="is-warning";
            $response["notification"]="Harap isi form dengan lengkap";
            header("HTTP/1.1 422 Incomplete fields");                    
        } else {
            $id=$this->doAdd($_data);
            if (!is_array($doc->error)) {
                $data=$this->doRead($id);
                $response=$doc->response("is-primary","resetButton",$data['id']);
            } else {
                $response=$doc->response("is-danger","resetButton");
                header("HTTP/1.1 422 Query Fails");
            }
        }
        return $response;
    }
    
    function postDel ($_data) {
        global $doc;
        if (!$_data['id']) {
            $response['id']='No ID number';
            header("HTTP/1.1 422 Incomplete fields");
        } else {
            $this->doDel($_data['id']);
            $response=$doc->response("is-primary","confirmClose",(INT)$_data['id']);
        }
        return $response;
    }
    
    function postUpdate ($_data) {
        global $fields,$gov2formfield,$doc;
        $errors=$this->gov2formfield->checkRequired($_data,$fields);
        if (is_array($errors)) {
            header("HTTP/1.1 422 Incomplete fields");
            $response=$errors;
        } else {
            $this->doUpdate($_data);
            if (!is_array($doc->error)) {
                $_update=$this->doRead($_data['id']);
                $response=$doc->response("is-info","toggleForm",$_update->id);
            } else {
                $response=$doc->response("is-danger","toggleForm",(INT)$_data['id']);
                header("HTTP/1.1 422 Incomplete fields");
            }
        }
        return $response;
    }
    
    function getBreadcrumb ($_root) {
        global $vars,$doc,$config;
        $_id=$this->setRememberId($vars['id']);
        $this->setBreadcrumb($_id);
        if (!$doc->error) {
            krsort($this->breadcrumb);
            $c=1;
            $url=json_decode(json_encode($config->webroot),true);
            if ($_root) { 
                $data[0]=array("caption"=>$_root,
                               "id"=>"0",
                               "level"=>"0",
                               "level_label"=>$_root);
            }
            if ($this->breadcrumb) {
                foreach($this->breadcrumb as $key => $val) {
                    $data[$c]=$val;
                    $c++;
                }
            }
            $response=$data;
        } else {
            $response=$doc->response("is-danger");
            header("HTTP/1.1 422 Query Table Fails");
        }
        return $response;        
    }
    
    function getChildren ($_id) {
        if (!$_id) {
            $data->level="1";        
        } else {
            $result=$this->doRead($_id);
            $data->level=$result['level']+1;
        }
        return $data;        
    }
    
    function getRecord ($_id) {
        if (!$_id) {
            $response['id']='Tidak ada nomor ID';
            header("HTTP/1.1 422 Tidak ada nomor ID");
        } else {
            $_id=$this->setRememberId($_id);
            $response=$this->doRead($_id);
            if (sizeof($response)==0) {$response=array("data"=>"empty","level"=>"1");}
        }
        return $response;        
    }
    
    function getRecords ($vars) {
        global $doc;
        $_id=$this->setRememberId($vars['id']);
        $data=$this->doBrowse($vars['scroll'],$_id);
        if (sizeof($data)==0) {$data=array("data"=>"empty","level"=>"1");}
        return $doc->responseGet($data);        
    }
    
    function getCount ($_id) {
        global $doc;
        $_id=$this->setRememberId($_id);
        $data=$this->doCountChildren($_id);
        return $doc->responseGet($data);        
    }
    
    function setRememberId ($_id,$_source="") {
        global $self;
        if ($_source) {
            $_classname=$_source;
        } else {
            $_classname=$self->className;
        }
        if ($_id==-1) {
            if ($_SESSION[$_classname.'_id']) {
                $_id=$_SESSION[$_classname.'_id'];
            } else {
                $_id=0;
            }
        } elseif ($_id==-2) {
            unset($_SESSION[$self->className.'_id']);
            $_id=0;
        } elseif ($_id!=0) {
            $_SESSION[$_classname.'_id']=$_id;
        }
    return $_id;
    }
}
