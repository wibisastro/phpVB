<?php namespace App\components\model;

class gov2table extends \Gov2lib\dsnSource {
	function __construct () {
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $GLOBALS['vueData']['geturl']='gov2table';
        $GLOBALS['vueData']['isTableActive']='true';
        $GLOBALS['vueData']['records']=0;
        $GLOBALS['vueData']['interval']=array(10,20,50,100);
        $GLOBALS['vueCreated'].='eventBus.$on("setItemPerPage", this.setItemPerPage);';
        $GLOBALS['vueMethods'].='setItemPerPage: function(data) {this.itemPerPage=data;},';
        
        parent::__construct(); 
	}

    function demo () {
        $GLOBALS['vueData']['scrollInterval']=$this->scrollInterval;
        $GLOBALS['vueData']['itemPerPage']=1;
        $GLOBALS['vueData']['interval']=array(1,2,5,10);
	}
    
    function count () {
		list($_link_id,$_name)=$this->connectDB();
		$query="SELECT count(id) as totalRecord FROM ".$this->tbl->tematik;
		$buffer=$this->queryDB($_name, $query,$_link_id);
		$result=mysqli_fetch_object($buffer);
	return $result;
	}
    
    function browse ($scroll=0) {
        $scrolled=$this->scroll($scroll);
		list($_link_id,$_name)=$this->connectDB();
		$query="SELECT * FROM ".$this->tbl->tematik." ORDER BY id LIMIT $scrolled";
		$daftar = $this->queryDB($_name, $query,$_link_id);
        $c=1;$page=1;
		while ($buffer = mysqli_fetch_object($daftar)) {
            $result[$c]=$buffer;
            $c++;
        }        
	return $result;
	}

    function read ($id) {
        $id+=0;
		list($_link_id,$_name)=$this->connectDB();
		$query="SELECT * FROM ".$this->tbl->tematik." WHERE id=$id";
		$buffer=$this->queryDB($_name, $query,$_link_id);
		$result=mysqli_fetch_object($buffer);
	return $result;
	}
}
?>