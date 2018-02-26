<?php namespace App\opd\model;
/********************************************************************
*	Date		: 6 Sep 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI
********************************************************************/

class opd extends \Gov2lib\env\dbConnect {
    function opdBrowse ($parent=0) {
    	global $dsn;
		list($_link_id,$_name)=$this->connectDB();
		$query="SELECT * FROM ".$dsn->tbl_opd." order by opd_id";
		$daftar = $this->queryDB($_name, $query,$_link_id);
        $c=0;
		while ($buffer = mysqli_fetch_object($daftar)) {$result[$c]=$buffer;$c++;}
	return $result;
	}

    function opdRead ($opd_id) {
    	global $dsn;
        $opd_id+=0;
		list($_link_id,$_name)=$this->connectDB();
		$query="SELECT * FROM ".$dsn->tbl_opd." WHERE opd_id=$opd_id";
		$buffer=$this->queryDB($_name, $query,$_link_id);
		$result=mysqli_fetch_object($buffer);
	return $result;
	}

    function opdAdd($data) {
    	global $dsn;
	    $data["account_id"]+=0;
	    $query ="INSERT INTO ".$dsn->tbl_opd." VALUES(null,0, '$data[nama]', '$data[singkatan]', '$data[kode]',$data[account_id],NOW())";
	    $id=$this->writeDB($query,"opdAdd",$dsn->tbl_opd);
	return $id;
	}

	function opdRemove($data) {
    	global $dsn;
	    $data["account_id"]+=0;
		$data["opd_id"]+=0;
	    $query ="DELETE FROM ".$dsn->tbl_opd." WHERE opd_id='$data[opd_id]'";
	    $this->writeDB($query,"opdRemove");
	return $data["opd_id"];
	}

	function opdUpdate($data,$opd_id) {
    	global $dsn;
	    $opd_id+=0;
	    $query ="UPDATE ".$dsn->tbl_opd." SET
	    	nama='$data[nama]',
	    	singkatan='$data[singkatan]',
	    	kode='$data[kode]'
	    WHERE opd_id=$data[opd_id]";
//	    echo $query;
	    $this->writeDB($query,"opd_update");
	}

	function opdHistory ($opd_id) {
		global $tbl_opd;
        static $c;
		$opd_id+=0;
		list($_link_id,$_name)=$this->connectDB();
		$query="SELECT opd_id,parent,nama,level FROM bappenas_staging.opd_h WHERE opd_id=$opd_id";
		//echo $query;
		$buffer=mysqli_fetch_object($this->queryDB($_name,$query,$_link_id));
        $c++;
		$this->history[$c]=$buffer;
		if ($buffer->parent>0){$this->opd_history($buffer->parent);}
	}

	function opdHistory_path ($data) {
		global $doc; //print_r($data);
		krsort($data);
		if($data[1]->opd_id){
			$result="<div class=\"alert alert-info\"><table>";
		} else {
			$result="";
		}
        $c=1;
		while (list($key,$val)=each($data)) {
			if ($val->opd_id && $c < sizeof($data)) {$result.="<tr><td><strong>".strtoupper($val->level)."</strong></td><td style=\"padding-left:20px\">: ".$doc->lnk($_SERVER['SCRIPT_NAME']."?parent=$val->opd_id",$val->nama)."</td></tr>";}
            else if ($val->opd_id) {$result.="<tr><td style=\"width:10%\"><strong>".strtoupper($val->level)."</strong></td><td style=\"padding-left:20px\">: ".$val->nama."</td></tr>";}
            $c++;
		}
		if($data[1]->opd_id){$result.="</table></div>";}
	return $result;
	}

	function breadcrumb_path ($data) {
		global $doc;
		krsort($data);

		$listlevel=array("program","opd","kegiatan","ppn","pkl");
		$result="<ol class=\"breadcrumb\"><li style=\"text-transform:uppercase;\"><a href=\"".$_SERVER[SCRIPT_NAME]."\">".$listlevel[0]."</a></li>
		";
        $c=1;
		while (list($key,$val)=each($data)) {
			$temp_tingkat=array_search($val->level, $listlevel);
			$tingkat=$temp_tingkat+1;
			$level=$listlevel[$tingkat];

			if ($val->opd_id && $c < sizeof($data)) {$result.="<li style=\"text-transform:uppercase;\"><span>".$doc->lnk($_SERVER['SCRIPT_NAME']."?parent=$val->opd_id",$level)."</span></li>";}
            else if ($val->opd_id) {$result.="<li class=\"active\" style=\"text-transform:uppercase;\"><span>".$level."</span></li>";}
            $c++;
		}
		$result.="</ol>";
	return $result;
	}

  	function opd_confirm_delete ($opd_id) {
		global $tbl_opd;
      	$opd_id+=0;
		list($_link_id,$_name)=$this->connectDB();
		$query="SELECT opd_id AS upper FROM $tbl_opd WHERE parent=$opd_id";
		$result = mysqli_fetch_object($this->queryDB($_name, $query,$_link_id));
	return $result;
	}
}
?>