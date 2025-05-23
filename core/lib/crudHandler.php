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
    
    function getBrowseTags ($_id,$_source,$_target,$_target2="",$_caption="") {
        global $doc,$config,$self;
        if (($_source=='wilayah' || $_source=='kementerian'  || $_source=='renstra') && $_id==-2 && !$self->ses->val[$this->className.'_id']) {
            $_id=trim($config->domain->attr['id']);
        }
        $_id=$this->setRememberId($_id,$_source);
        $data=$this->doBrowseTags($_id,$_source,$_target,$_target2,$_caption);
        if (sizeof($data)==0) {$data=array("data"=>"empty");}
        return $doc->responseGet($data);        
    }
    
    function postTagging ($_data,$_source,$_target,$_target2="",$_caption="") {
        global $doc,$config;
        if (!$_data['source_id'] || !$_data['target_id']) {
            $response["class"]=$config->css->attr['is-warning'];
            $response["notification"]="Pasangan ID tidak lengkap";
            header("HTTP/1.1 422 Incomplete fields");                    
        } else {
            $id=$this->doTagging($_data,$_source,$_target,$_target2,$_caption);
            if (!is_array($doc->error)) {
                $data=$this->doRead($id);
                $response=$doc->response("is-primary","",$data['id']);
            } else {
                $response["class"]="is-danger";
                // list($_caption,$_text)=explode(":",$doc->error);
                // $response["notification"]="$_caption: $_text";
                $response["notification"]=$doc->error;
                $response["callback"]="infoSnackbar";
                header("HTTP/1.1 422 Insert Fails");
            }
        }
        return $response;
    }

    function postAdd ($_data,$fields=0) {
        global $doc,$config,$scriptID,$requester;
        if ($fields) {
            $errors=$this->gov2formfield->checkRequired($_data,$fields);   
        } else {
            $errors=$this->gov2formfield->checkRequired($_data,$this->fields); 
        }
        if (is_array($errors)) {
            $response=$errors;
            if ($config->css->warning) {
                $response["class"]=(string) $config->css->warning;
            } else {
                $response["class"]="is-warning";   
            }
            $response["notification"]="Harap isi form dengan lengkap";
            header("HTTP/1.1 422 Incomplete fields");                    
        } else {
            $id=$this->doAdd($_data);   
            
            // if ($scriptID=='proyek') {
            //     return $id;
            // }
            
            if (!is_array($doc->error)) {
                $data=$this->doRead($id);
                $response=$doc->response("is-primary","resetButton",$data['id']);
            } else {
                $response=$doc->response("is-danger","resetButton");
                if ($requester=='browser') {
                    header("HTTP/1.1 422 Query Fails");
                }
            }
        }
        return $response;
    }
    
    function postDel ($_data) {
        global $doc,$requester;
        if (!$_data['id']) {
            $response['id']='No ID number';
            if ($requester=='browser') {
                header("HTTP/1.1 422 Incomplete fields");
            }
        } else {
            $this->doDel($_data['id']);
            $response=$doc->response("is-primary","confirmClose",(INT)$_data['id']);
        }
        return $response;
    }
    
    function postUpdate ($_data) {
        global $fields,$gov2formfield,$doc,$requester;
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
                if ($requester=='browser') {
                    header("HTTP/1.1 422 Incomplete fields");
                }
            }
        }
        return $response;
    }
    
    function getBreadcrumb ($_root,$_id=0,$_caption="",$_code="") {
        global $vars,$doc,$config;
        unset($this->breadcrumb);
        if (!$_id) {$_id=$vars['id'];}
        $_id=$this->setRememberId($_id,$_caption);
        $this->setBreadcrumb($_id,$_caption,$_code); 
        if (!$doc->error) {
            krsort($this->breadcrumb);
            $c=1;
            $url=json_decode(json_encode($config->webroot),true);
            if ($_root) { 
                $data[0]=array("caption"=>$_root,
                               "id"=>"0",
                               "level"=>"0",
                               "code"=>"0",
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
    
    function getRecords ($vars,$parent_name="") {
        global $doc,$self;
        $_id=$this->setRememberId($vars['id']);
        $data=$this->doBrowse($vars['scroll'],$_id,$parent_name);
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
            if ($self->ses->val[$_classname.'_id']) {
                $_id=$self->ses->val[$_classname.'_id'];
            } else {
                $_id=0;
            }
        } elseif ($_id==-2) {
            $self->ses->val[$self->className.'_id']="";
            $_id=0;
        } elseif ($_id!=0) {
            $self->ses->val[$_classname.'_id']=$_id;
        }
        $self->ses->sesSave($self->ses->val);
        return $_id;
    }
}
