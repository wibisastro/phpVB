<?php namespace App\gov2login\model;

class admin extends \Gov2lib\crudHandler {
	function __construct () {
        global $doc,$config;
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $doc->body("className",$this->className);
        parent::__construct($config->domain->attr['dsn']);  
        $this->tbl->table=$this->tbl->member;
        $this->tbl->wilayah=$this->tbl->wilayah;
	}
    
    function loadTable ($_scrollInterval) {
        //---gov2pagination
        $GLOBALS['vueData']['itemPerPage']=10;
        $GLOBALS['vueData']['scrollInterval']=$_scrollInterval;
        
        //---gov2formfield
        $GLOBALS['vueData']['readOnly']=false;
        $GLOBALS['vueData']['fieldurl']=$this->className.'/fields'; //<-overwrite default
	}
    
    function memberBrowse ($scroll) {
        global $uri;
        try {
            $scrolled=$this->scroll($scroll);
            $query="SELECT * FROM ".$this->tbl->table." WHERE %s LIMIT $scrolled";
            $results = \DB::query($query,$this->memberWhere());
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage().":".$uri);
		}
	return $results;
	}
    
    function memberCount () {
        global $uri;
        try {
            $query="SELECT count(id) as totalRecord FROM ".$this->tbl->table." WHERE %s";
            $results = \DB::queryFirstRow($query,$this->memberWhere());
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage().":".$uri);
		}
	   return $results;
	}
    
    function memberWhere () {
        $_where = new \WhereClause('and');
        $_where->add('role!=%s', 'guest');
        $_where->add('role!=%s', 'owner');
        $_where->add('role!=%s', 'developer');
    return $_where;
    }
    
    function readKabs ($kab_id) {
        global $uri,$doc;
        try {
            $_query="SELECT parent_id FROM ".$this->tbl->wilayah." WHERE id=%i";
            $result['prov'] = \DB::queryFirstField($_query,$kab_id);
            $_query="SELECT id FROM ".$this->tbl->wilayah." WHERE parent_id=%i";
            $result['kab'] = \DB::query($_query,$result['prov']);
        } catch (\MeekroDBException $e) {
			$doc->exceptionHandler($e->getMessage().":".$uri);
		}
	   return $result;
	}
    
    function accountAdminRead ($id) {
        global $uri,$doc;
        try {
            $_query="SELECT * FROM ".$this->tbl->table." WHERE id=%i";
            $_result = \DB::queryFirstRow($_query,$id);
        } catch (\MeekroDBException $e) {
			$doc->exceptionHandler($e->getMessage().":".$uri);
		}
	   return $_result;
	}
    
    function accountCheckExist ($id,$kab_id) {
        global $uri,$doc;
        try {
            list($_link_id,$_name)=$this->connectDB();
            $id+=0;
            $kab_id+=0;
            $_query="SELECT id FROM ".$this->tbl->table." WHERE account_id=$id AND kab_id=$kab_id";
            $_result=mysqli_fetch_array($this->queryDB($_name, $_query,$_link_id));
        } catch (\Exception $e) {
			$doc->exceptionHandler($e->getMessage().":".$uri);
		}
	   return $_result['id'];
	}
    
	function accountUpdateRole ($id,$role) {
        global $uri,$doc;
        try {
            list($_link_id,$_name)=$this->connectDB();
            $id+=0;
            $_query="UPDATE ".$this->tbl->table." SET role='".$role."' WHERE id=$id";
            $_result=mysqli_fetch_array($this->queryDB($_name, $_query,$_link_id));
        } catch (\Exception $e) {
			$doc->exceptionHandler($e->getMessage().":".$uri);
		}
    }
   
    function propagasi ($list="",$role="") {
        global $config,$uri,$doc;
        $_result=0;
        try {
            $_wilayah=$this->readKabs(trim($config->domain->attr['id']));
            foreach ($list as $_key => $_id) {
                foreach ($_wilayah['kab'] as $_key2 => $_kab) {
                    $_account=$this->accountAdminRead($_id);
                    $_exist=$this->accountCheckExist($_account['account_id'],$_kab['id']);
                    if ($_exist) {
						$this->accountUpdateRole($_exist,$role);
						$_result++;
                    } else {
                        unset($_account['id']);
                        $_account['role']=$role;
                        $_account['kab_id']=$_kab['id'];
                        $_account['prov_id']=$_wilayah['prov'];
                        \DB::insert("member", $_account);
					    $_result+=\DB::affectedRows();
                    }
                }   
            }
        } catch (\MeekroDBException $e) {
			$doc->exceptionHandler($e->getMessage().":".$uri);
		}
        return $_result;
	}
}
?>