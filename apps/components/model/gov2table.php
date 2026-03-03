<?php namespace App\components\model;

class gov2table extends \Gov2lib\dsnSource {
	function __construct () {
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $GLOBALS['vueData']['geturl']='/components/gov2table';
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
    
    private function loadJson () {
        $path = __DIR__ . '/../json/table.json';
        $all  = json_decode(file_get_contents($path), true);
        return $all ?: [];
    }

    function count () {
        $all    = $this->loadJson();
        $result = new \stdClass();
        $result->totalRecord = count($all);
        return $result;
    }

    function browse ($scroll=0) {
        $all      = $this->loadJson();
        $rows     = array_values($all);
        $interval = $this->scrollInterval ?: 4;
        $scroll   = max(1, (int)$scroll);
        $offset   = ($scroll - 1) * $interval;
        $slice    = array_slice($rows, $offset, $interval);
        if (empty($slice)) return ['data' => 'empty'];
        $result = [];
        foreach ($slice as $i => $row) {
            $result[$i + 1] = $row;
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