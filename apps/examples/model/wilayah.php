<?php namespace App\examples\model;

class wilayah extends \Gov2lib\crudHandler {
	function __construct () {
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        parent::__construct();  
        $this->tbl->table=$this->tbl->wilayah;
	}
    
    function loadTable ($_scrollInterval) {
        //---gov2pagination
        $GLOBALS['vueData']['itemPerPage']=10;
        $GLOBALS['vueData']['scrollInterval']=$_scrollInterval;
        
        //---gov2formfield
        $GLOBALS['vueData']['readOnly']=false;
        $GLOBALS['vueData']['fieldurl']=$this->className.'/fields'; //<-overwrite default
	}
    
 /*
    function breadcrumb ($_id=0) {
        static $_c;
		$_id+=0;
		list($_link_id,$_name)=$this->connectDB();
		$query="SELECT * FROM ".$this->tbl->wilayah." WHERE id=$_id";
		$buffer=mysqli_fetch_object($this->queryDB($_name,$query,$_link_id));
        if ($buffer->nama) {
            $_c++;
            $this->breadcrumb[$_c]['caption']=$buffer->nama;
            $this->breadcrumb[$_c]['id']=$buffer->id;
            $this->breadcrumb[$_c]['level']=$buffer->level;
            if ($buffer->parent_id>0){$this->breadcrumb($buffer->parent_id);}
        }
	}

    function count ($parent_id) {
        $parent_id+=0;
		list($_link_id,$_name)=$this->connectDB();
		$query="SELECT count(id) as totalRecord FROM ".$this->tbl->wilayah." WHERE parent_id=$parent_id";
		$buffer=$this->queryDB($_name, $query,$_link_id);
		$result=mysqli_fetch_object($buffer);
	return $result;
	}
        
    function browse ($scroll,$parent_id=0) {
        $scrolled=$this->scroll($scroll);
        $parent_id+=0;
		list($_link_id,$_name)=$this->connectDB();
		$query="SELECT * FROM ".$this->tbl->wilayah." WHERE parent_id=$parent_id LIMIT $scrolled";
		$daftar = $this->queryDB($_name, $query,$_link_id);
        $c=1;
		while ($buffer = mysqli_fetch_object($daftar)) {
            $result[$c]=$buffer;
            $c++;
        }        
	return $result;
	}

    function read ($id) {
        $id+=0;
		list($_link_id,$_name)=$this->connectDB();
		$query="SELECT * FROM ".$this->tbl->wilayah." WHERE id=$id";
		$buffer=$this->queryDB($_name, $query,$_link_id);
		$result=mysqli_fetch_object($buffer);
	return $result;
	}

    function add($data) {
	    $data["parent_id"]+=0;
	    $query ="INSERT INTO ".$this->tbl->wilayah." VALUES(null, $data[parent_id],'$data[level]', '$data[kode]', '$data[nama]','1')";
	    $id=$this->writeDB($query,"Add",$this->tbl_wilayah);
	return $id;
	}

	function del($data) {
		$data['id']+=0;
	    $query ="DELETE FROM ".$this->tbl->wilayah." WHERE id='$data[id]'";
	    $this->writeDB($query,"Del");
	}

	function update($data,$id) {
	    $id+=0;
	    $query ="UPDATE ".$this->tbl->wilayah." SET
	    	nama='$data[nama]',
	    	kode='$data[kode]',
	    	level='$data[level]'
	    WHERE id=$data[id]";
	    $this->writeDB($query,"_update");
	}
    */
}
?>