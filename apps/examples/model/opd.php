<?php namespace App\examples\model;

class opd extends \Gov2lib\dsnSource {
	function __construct () {
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
		$this->controller=__DIR__."/../".$this->className.".php";
        parent::__construct();  
        list($this->dbLink,$this->dbName)=$this->connectDB('opd');
	}
    
    function loadTable ($_scrollInterval=1000) {
        //---gov2pagination
        $GLOBALS['vueData']['itemPerPage']=10;
        $GLOBALS['vueData']['scrollInterval']=$_scrollInterval;
        
        //---gov2formfield
        $GLOBALS['vueData']['readOnly']=false;
        $GLOBALS['vueData']['fieldurl']=$this->className.'/fields';
	}
    
    function count () {
		$query="SELECT count(id) as totalRecord FROM ".$this->tbl->opd;
		$result = \DB::queryFirstRow($query);		
	return $result;
	}
    
    function browse ($scroll=0) {
        try {
            $scrolled=$this->scroll($scroll);
            $query="SELECT * FROM ".$this->tbl->opd." ORDER BY id LIMIT $scrolled";
            $results = \DB::query($query);
        } catch (\MeekroDBException $e) {
			$this->exceptionHandler($e->getMessage());
		}
	return $results;
	}

    function read ($id) {
        $id+=0;
		$query="SELECT * FROM ".$this->tbl->opd." WHERE id=$id";
		$result = \DB::queryFirstRow($query);
	return $result;
	}

    function add($data) {
	    $data["account_id"]+=0;
	    $query ="INSERT INTO ".$this->tbl->opd." VALUES(null,0, '$data[nama]', '$data[singkatan]', '$data[kode]',$data[account_id],NOW())";
	    $id=$this->writeDB($query,"Add",$this->tbl_opd);
	return $id;
	}

	function del($data) {
		$data['id']+=0;
	    $query ="DELETE FROM ".$this->tbl->opd." WHERE id='$data[id]'";
	    $this->writeDB($query,"Del");
	}

	function update($data,$id) {
	    $id+=0;
	    $query ="UPDATE ".$this->tbl->opd." SET
	    	nama='$data[nama]',
	    	kode='$data[kode]',
	    	singkatan='$data[singkatan]'
	    WHERE id=$data[id]";
	    $this->writeDB($query,"_update");
	}
}
?>