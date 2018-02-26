<?php namespace Gov2lib\env;
/*
Author		: Wibisono Sastrodiwiryo
Date		: Thursday, November 20, 2008
Copyleft	: eGov Lab UI
Contact		: wibi@alumni.ui.ac.id
Version		: 0.0.1 -> 23-Nov-06, 13:55  
			: 0.0.2 -> menghilangkan penggunaan 2 table untuk supporter dan member
			: 0.2.0 -> pakai facebook connect
			: 0.2.1 -> integrasi facebook connect dan member table, Monday, June 08, 2009
			: 0.2.2 -> ubah privilege agar dapat pakai multi privelege, Tuesday, August 04, 2009
			: 0.2.3 -> ubah privilege agar dapat pakai multi privelege, Tuesday, Monday, December 14, 2009
			: 0.2.4 -> ubah admin jadi supporter, Friday, May 13, 2011
			: 0.3.0 -> downgrade ke 0.0.1 untuk publikasi open data kpu, 15 April 2014
*/
class session extends customException {
	function __construct () {
	    $this->timeout			= 120; #---seconds
		$this->timeout_session	= 480; #---minutes
//		$this->online();
	}

	function sesread ($cookies) {
		if ($cookies) {
			$cookie=explode("&&", $cookies);
			for ($a=0;$a<=sizeof($cookie)-1;$a++) {$item=explode("&", $cookie[$a]);$sesarray[$item[0]]=$item[1];}
		}
	return $sesarray;
	}

	function sesvars($session_id) {
		global $tbl_session;
		list($db_link_id,$db_name)=$this->connect_db("account");
		$session_id+=0;
		$query="SELECT * FROM $tbl_session WHERE session_id = $session_id";
		$result=mysql_fetch_object($this->read_db($db_name,$query,$db_link_id));
	return $result->vars;
	}

	function sesupdate($vars,$session_id) {
		global $tbl_session;
		list($db_link_id,$db_name)=$this->connect_db("account");
		$query="UPDATE $tbl_session SET vars='$vars' WHERE session_id = $session_id";
		$this->read_db($db_name, $query, $db_link_id) or die ("update vars:".mysql_error());
	}

	function sesload ($key, $val, $cookies="") {
		$ses=explode("&&", $cookies);
		for ($a=0;$a<=sizeof($ses)-1;$a++) {$item=explode("&", $ses[$a]);$sesarray[$item[0]]=$item[1];}
		if (!$cookies) {unset($sesarray);}
		$sesarray[$key]=$val;$a=0;
		while (list($key1,$val1)=each($sesarray))  {$auth[$a]=$key1."&".$val1;$a++;}
		$result=implode("&&", $auth);
	return $result;
	}

	function sesunload ($del, $val, $cookies="") {
		if ($cookies) $ses=explode("&&", $cookies);
		for ($a=0;$a<=sizeof($ses)-1;$a++) {$item=explode("&", $ses[$a]);$sesarray[$item[0]]=$item[1];}
		$a=0;
		while (list($key,$val)=each($sesarray))  {if ($del != $key) {$auth[$a]=$key."&".$val;$a++;}}
		$result=implode("&&", $auth);
	return $result;
	}

	function save_cookie () {
		while(list($key,$val)=each($this->cookies)) {$cookies[$key]="cookie[$key]=$val";}
		setcookie(cookie_name,implode("&",$cookies), 0, "/");
	}

	function sessave ($auth) {
		setcookie("member_".cookie_name,$auth, 0, "/");
	}

}
?>