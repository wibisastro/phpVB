<?php namespace Gov2lib;
    
class crudModel extends dsnSource {
	function __construct ($_dsn="") {
        parent::__construct(); 
        list($_link_id,$_name)=$this->connectDB($_dsn);
	}
    
    function doBrowseTags ($source_id,$_source,$_target,$_target2="") {
        try {
            $query="SELECT *,
            ".$_target."_nama AS target_nama,
            ".$_target."_id AS target_id,
            ".$_source."_id AS source_id";
            if ($_target2) {
                $query.=",".$_target2."_id AS target2_id ";
            }
            $query.=" FROM ".$this->tbl->table." WHERE ".$_source."_parent=%i";
            if ($_target2) {
                $query.=" AND ".$_target2."_id=%i";
            }
            $results = \DB::query($query,$source_id,$_SESSION[$_target2."_id"]);
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage());
		}
	return $results;
	}
    
    function doTagging($data,$_source,$_target,$_target2) {
        try {
            if ($_target2) {
                $query="SELECT * FROM ".$this->tbl->table." WHERE ".$_source."_id=%i AND ".$_target."_id=%i AND ".$_target2."_id=%i";
                $_tagged=\DB::queryFirstRow($query,$data['source_id'],$data['target_id'],$_SESSION[$_target2."_id"]);            
            } else {
                $query="SELECT * FROM ".$this->tbl->table." WHERE ".$_source."_id=%i AND ".$_target."_id=%i";
                $_tagged=\DB::queryFirstRow($query,$data['source_id'],$data['target_id']);
            }
            if ($_tagged['id']) {
                throw new \Exception('AlreadyTagged: '.$_tagged[$_target.'_nama'].' source_id='.$data['source_id']);
            } else {
                $query="SELECT * FROM ".$this->tbl->source." WHERE id=%i";
                $_sourceData=\DB::queryFirstRow($query,$data['source_id']);
                
                $query="SELECT * FROM ".$this->tbl->target." WHERE id=%i";
                $_targetData=\DB::queryFirstRow($query,$data['target_id']);
                
                $_insert[$_source.'_id']=$data['source_id'];
                $_insert[$_source.'_parent']=$_sourceData['parent_id'];                
                $_insert[$_source.'_nama']=$_sourceData['nama'];
                $_insert[$_source.'_level']=$_sourceData['level_label'];
                $_insert[$_target.'_id']=$data['target_id'];
                $_insert[$_target.'_nama']=$_targetData['nama'];
                
                if ($_target2) {
                    $query="SELECT * FROM ".$this->tbl->$_target2." WHERE id=%i";
                    $_target2Data=\DB::queryFirstRow($query,$_SESSION[$_target2.'_id']);
                }
                $columns = \DB::columnList($this->tbl->table);
                if (in_array($_target.'_level',$columns)) {
                    $_insert[$_target.'_level']=$_targetData['level_label'];
                }
                if (in_array($_target2.'_id',$columns)) {
                    $_insert[$_target2.'_id']=$_SESSION[$_target2.'_id'];
                }
                if (in_array($_target2.'_nama',$columns)) {
                    $_insert[$_target2.'_nama']=$_target2Data['nama'];
                }
                if (in_array($_target2.'_level',$columns)) {
                    $_insert[$_target2.'_level']=$_target2Data['level_label'];
                }
                if (in_array($_target2.'_parent',$columns)) {
                    $_insert[$_target2.'_parent']=$_target2Data['parent_id'];
                }
                \DB::insert($this->tbl->table, $_insert);
                $_id = \DB::insertId();
                return $_id;
            }
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage());
        } catch (\Exception $e) {
			$this->exceptionHandler($e->getMessage());
		}
	}
    
    function setBreadcrumb ($_id=0) {
        static $_c;
        try {
            $query="SELECT * FROM ".$this->tbl->table." WHERE id=%i";
            $results = \DB::query($query,$_id);
            if (is_array($results)) {
                foreach ($results as $row) {
                    $_c++;
                    $this->breadcrumb[$_c]['caption']=$row['nama'];
                    $this->breadcrumb[$_c]['id']=$row['id'];
                    $this->breadcrumb[$_c]['level']=$row['level'];
                    $this->breadcrumb[$_c]['level_label']=$row['level_label'];
                    if ($row['parent_id']>0) {$this->setBreadcrumb($row['parent_id']);}
                }
            }
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage());
		}
	}
    
	function doUpdate($data) {
        unset($data['cmd']);
        $_fields=$data;
        $columns = \DB::columnList($this->tbl->table);
        if (in_array("parent_id",$columns)) {
        //    if ($data['parent_id']) {
                $_recursive=array('parent_id' => $data['parent_id']+0,
                                  'level_label' => $this->gov2formfield->getLevel($this->fields,$data['level']),
                                  'level' => $data['level'],
                                  'created_at' => date('Y-m-d H:i:s')
                                 );
                $_fields=array_merge($_fields,$_recursive);
        //    }
        } else {
            unset($_fields['parent_id']);
        }
        try {
            \DB::update($this->tbl->table, $_fields, "id=%i", $data['id']+0);
            if ($data['id'] && in_array("parent_id",$columns)) {
                $this->updateChildren($data['id']);    
            }
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage());
		}
	}
    
	function doDel($_id=0) {
        try {
            $data=$this->doRead($_id);
            \DB::delete($this->tbl->table, "id=%i", $_id);
            if ($data['parent_id']) {
                $this->updateChildren($data['parent_id']);    
            }        
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage());
		}
	}

    function updateChildren($_id) {
        $_children=$this->doCountChildren($_id);
        $_fields=array("children"=>$_children['totalRecord']);
        try {
            \DB::update($this->tbl->table, $_fields, "id=%i", $_id+0);
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage());
		}
	}
    
    function doAdd($data) {
        unset($data['cmd']);
        $_fields=$data;
        try {
            $columns = \DB::columnList($this->tbl->table);
            if (in_array("parent_id",$columns)) {
                $level_label=$this->gov2formfield->getLevel($this->fields,$data['level']);
                $_parent=$this->doRead($data['parent_id']);
                for ($i = $_parent['level'] ; $i >= 1; $i--) {
                    if ($i == $_parent['level']) {
                        $_fields[$_parent['level_label']."_id"]=$_parent['id'];
                    } else {
                        $_parent_label=$this->gov2formfield->getLevel($this->fields,$i);
                        $_fields[$_parent_label."_id"]=$_parent[$_parent_label."_id"];
                    }
                }
                $_recursive=array('parent_id' => $data['parent_id']+0,
                                  'level_label' => $level_label,
                                  'level' => $data['level'],
                                  'created_at' => date('Y-m-d H:i:s')
                                 );
                $_fields=array_merge($_fields,$_recursive);
            } else {
                unset($_fields['parent_id']);
            }

            \DB::insert($this->tbl->table, $_fields);
            $_id = \DB::insertId();
            if ($data['parent_id'] && in_array("parent_id",$columns)) {
                $this->updateChildren($data['parent_id']);    
            }
            return $_id;
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage());
		} catch (\Exception $e) {
			$this->exceptionHandler($e->getMessage());
		}
	}
    
    function doRead ($_id=0) {
		$query="SELECT * FROM ".$this->tbl->table." WHERE id=%i";
	return \DB::queryFirstRow($query,$_id);
	}
    
    function doCountChildren ($parent_id="") {
        if (isset($parent_id)) {$WHERE="WHERE parent_id=%i";}
		$query="SELECT count(id) as totalRecord FROM ".$this->tbl->table." $WHERE";	
	   return \DB::queryFirstRow($query,$parent_id);
	}
    
    function doBrowse ($scroll,$parent_id="") {
        global $uri;
        try {
            $scrolled=$this->scroll($scroll);
            if (isset($parent_id)) {$WHERE="WHERE parent_id=%i";}
            $query="SELECT * FROM ".$this->tbl->table." $WHERE LIMIT $scrolled";
            $results = \DB::query($query,$parent_id);
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage().":".$uri);
		}
	return $results;
	}
}
