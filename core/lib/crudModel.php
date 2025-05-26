<?php namespace Gov2lib;
    
class crudModel extends dsnSource {
	function __construct ($_dsn="") {
        parent::__construct(); 
        list($_link_id,$_name)=$this->connectDB($_dsn);
	}
    
    function doBrowseTags ($source_id,$_source,$_target,$_target2="",$_caption="") {
        try {
            $query="SELECT *,
            ".$_target."_".$_caption." AS target_".$_caption.",
            ".$_target."_id AS target_id,
            ".$_source."_id AS source_id";
            if ($_target2) {
                $query.=",".$_target2."_id AS target2_id ";
            }
            $query.=" FROM ".$this->tbl->table." WHERE ".$_source."_parent=%i";
            if ($_target2) {
                $query.=" AND ".$_target2."_id=%i";
            }
            $results = \DB::query($query,$source_id,$this->ses->val[$_target2."_id"]);
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler('doBrowseTags:'.$e->getMessage());
		}
	    return $results;
	}
    
    function doTagging ($data,$_source,$_target,$_target2,$_caption) {
        try {
            if ($_target2) {
                $query="SELECT * FROM ".$this->tbl->table." WHERE ".$_source."_id=%i AND ".$_target."_id=%i AND ".$_target2."_id=%i";
                $_tagged=\DB::queryFirstRow($query,$data['source_id'],$data['target_id'],$this->ses->val[$_target2."_id"]);            
            } else {
                $query="SELECT * FROM ".$this->tbl->table." WHERE ".$_source."_id=%i AND ".$_target."_id=%i";
                $_tagged=\DB::queryFirstRow($query,$data['source_id'],$data['target_id']);
            }
            
            if ($_tagged['id']) {
                throw new \Exception('AlreadyTagged: '.$_tagged[$_target.'_'.$_caption].' source_id='.$data['source_id']);
            } else {
                $query="SELECT * FROM ".$this->tbl->source." WHERE id=%i";
                
                $_sourceData=\DB::queryFirstRow($query,$data['source_id']);
                
                $query="SELECT * FROM ".$this->tbl->target." WHERE id=%i";
                $_targetData=\DB::queryFirstRow($query,$data['target_id']);
                
                $_insert[$_source.'_id']= intval($data['source_id']);
                $_insert[$_source.'_parent']=$_sourceData['parent_id'];     
                
                $_insert[$_source.'_nama']=$_sourceData['nama'];
                $_insert[$_source.'_level']=intval($_sourceData['level_label']);
                $_insert[$_target.'_id']=intval($data['target_id']);
                $_insert[$_target.'_'.$_caption]=$_targetData[$_caption];
                
                if ($_target2) {
                    $query="SELECT * FROM ".$this->tbl->$_target2." WHERE id=%i";
                    $_target2Data=\DB::queryFirstRow($query,$this->ses->val[$_target2.'_id']);
                }
                $columns = \DB::columnList($this->tbl->table);
                if (in_array($_target.'_level',$columns)) {
                    $_insert[$_target.'_level']=$_targetData['level_label'];
                }
                if (in_array($_target2.'_id',$columns)) {
                    $_insert[$_target2.'_id']=$this->ses->val[$_target2.'_id'];
                }
                if (in_array($_target2.'_'.$_caption,$columns)) {
                    $_insert[$_target2.'_'.$_caption]=$_target2Data[$_caption];
                }
                if (in_array($_target2.'_level',$columns)) {
                    $_insert[$_target2.'_level']=$_target2Data['level_label'];
                }
                if (in_array($_target2.'_parent',$columns)) {
                    $_insert[$_target2.'_parent']=$_target2Data['parent_id'];
                }
                if ($_source=='wilayah') {
                    $_query_w="SELECT * FROM ".$this->tbl->wilayah." WHERE id=%i";
                    $_wilayah = \DB::queryFirstRow($_query_w,$data['source_id']);
                    switch ($_wilayah['level']) {
                        case "3":
                            $_query_w="SELECT 0 AS kel_id, kec.id AS kec_id, 
                            kab.id AS kab_id, kab.parent_id AS prov_id
                            FROM ".$this->tbl->wilayah." AS kec
                            LEFT JOIN ".$this->tbl->wilayah." AS kab
                            ON kec.parent_id=kab.id
                            WHERE kec.id=%i";
                        break;
                        case "4":
                            $_query_w="SELECT kel.id AS kel_id, 
                            kec.id AS kec_id, kab.id AS kab_id, 
                            kab.parent_id AS prov_id 
                            FROM ".$this->tbl->wilayah." AS kel
                            LEFT JOIN ".$this->tbl->wilayah." AS kec
                            ON kel.parent_id=kec.id
                            LEFT JOIN ".$this->tbl->wilayah." AS kab
                            ON kec.parent_id=kab.id
                            WHERE kel.id=%i";
                        break;
                    }
                    if ($_wilayah['level'] == '2') {
                        $_insert['provinsi_id']=$_wilayah['parent_id'];
                        $_insert['kabupaten_id']=$_wilayah['id'];
                    } else {
                        try {
                            $_wilayah = \DB::queryFirstRow($_query_w,$_wilayah['id']);
                            $_insert['provinsi_id']=$_wilayah['prov_id']+0;
                            $_insert['kabupaten_id']=$_wilayah['kab_id']+0;
                            $_insert['kecamatan_id']=$_wilayah['kec_id']+0;
                            $_insert['kelurahan_id']=$_wilayah['kel_id']+0;
                        } catch (\MeekroDBException $e) {
                            $this->exceptionHandler($e->getMessage().":".$uri);
                        }   
                    }
                }
                
                \DB::insert($this->tbl->table, $_insert);
                $_id = \DB::insertId();
                return $_id;
            }
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler('doTagging:'.$e->getMessage());
        } catch (\Exception $e) {
			$this->exceptionHandler($e->getMessage());
		}
	}
    
    function setBreadcrumb ($_id=0,$_caption="",$_code="") {
        static $_c;
        try {
            $query="SELECT * FROM ".$this->tbl->table." WHERE id=%i";
            $results = \DB::query($query,$_id);
            if (is_array($results)) {
                foreach ($results as $row) {
                    $_c++;
                    if ($_caption) {
                        $this->breadcrumb[$_c]['caption']=$row[$_caption];
                    } else {
                        $this->breadcrumb[$_c]['caption']=$row['nama'];    
                    }
                    if ($_code) {
                        $this->breadcrumb[$_c]['code']=$row[$_code];
                    }
                    $this->breadcrumb[$_c]['id']=$row['id'];
                    $this->breadcrumb[$_c]['level']=$row['level'];
                    $this->breadcrumb[$_c]['level_label']=$row['level_label'];
                    if ($row['parent_id']>0) {
                        $this->setBreadcrumb($row['parent_id'],$_caption,$_code);
                    }
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
            // if ($data['parent_id']) {

            // -----PR untuk mencegah memasukkan level_label secara sembarangan
  
            // 'level_label' => $this->gov2formfield->getLevel($this->fields,$data['level']),
  
                $_recursive=array(
                    'parent_id' => $data['parent_id']+0,
                    'level_label' => $data['level_label'],
                    'level' => $data['level'],
                    'created_at' => date('Y-m-d H:i:s')
                );
                $_fields=array_merge($_fields,$_recursive);
            // }
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
        global $scriptID;
        $_account_id = isset($this->ses->val['account_id']) ? trim((string)$this->ses->val['account_id']) : '';
        unset($data['cmd']);
        $_fields=$data;
        try {
            $columns = array_keys(\DB::columnList($this->tbl->table));
            if (in_array("parent_id",$columns)) {
                $level_label=$this->gov2formfield->getLevel($this->fields,$data['level'],$data['level_label']);
                $_parent=$this->doRead($data['parent_id']);
                for ($i = $_parent['level'] ; $i >= 1; $i--) {
                    if ($i == $_parent['level']) {
                        $_fields[$_parent['level_label']."_id"]=$_parent['id'];
                    } else {
                        $_grandparent=$this->doRead($_parent['parent_id']);
                        $_parent_label=$this->gov2formfield->getLevel($this->fields,$i,$_grandparent['level_label']);
                        
                        $_fields[$_parent_label."_id"]=$_parent[$_parent_label."_id"];
                        $_parent=$_grandparent;
                        /*
                        if ($scriptID=='instance_keuangan') {
                            print_r($_fields);
                        }
                        */
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
                $_flat=array('created_by' => is_numeric($_account_id) ? (int)$_account_id : 0,
                            'created_at' => date('Y-m-d H:i:s'));
                $_fields=array_merge($_fields,$_flat);
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
    
    function doRead ($id=0) {
        global $doc;
		$_query="SELECT * FROM ".$this->tbl->table." WHERE id=%i";
        try {
           $_response=\DB::queryFirstRow($_query,$id); 
        } catch (\MeekroDBException $e) {
			$doc->exceptionHandler($e->getMessage());
		}
	    return $_response;
	}
    
    function doCountChildren ($parent_id="") {
        if (isset($parent_id)) {$WHERE="WHERE parent_id=%i";}
		$query="SELECT count(id) as totalRecord FROM ".$this->tbl->table." $WHERE";	
	   return \DB::queryFirstRow($query,$parent_id);
	}
    
    function doBrowse ($scroll,$parent_id="",$parent_id_name="") {
        global $uri;
        try {
            $scrolled=$this->scroll($scroll);
            if ($parent_id_name) {$_parent=$parent_id_name."_id";}
            else {$_parent="parent_id";}
            if (isset($parent_id)) {$WHERE="WHERE $_parent=%i";}
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
}
