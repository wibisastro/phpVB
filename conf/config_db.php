<?
/*
Author		: Wibisono Sastrodiwiryo
Date		: Thursday, November 20, 2008
Copyright	: PT Cyber Gatra Loka (cybergl.co.id)
Contact		: wibi@cybergl.co.id
Version		: 0.1.0
*/

#---------------------------------------cybergl platform tables configuration
	$tbl_online			= "online";
	$tbl_session		= "session";
	$tbl_text			= "text";
	$tbl_unauth			= "unauthorized";
#---------------------------------------database classes

class db_connection {
	function connect_db($db_server="") {
		static $recent_random;
		switch ($db_server) {
			case "account":
				$db["sys"]["user"]   = "root";
				$db["sys"]["pass"]   = "";
				$db["sys"]["host"]   = "localhost";
				$db_name		 = "";
				$db_link_id=mysql_pconnect($db["sys"]["host"], $db["sys"]["user"], $db["sys"]["pass"]) or die("Unable to connect to SQL server 'system'");	
			break;
			default:
				$db["master"]["user"]	= "root";
				$db["master"]["pass"]	= "";
				$db["master"]["host"]	= "localhost";
				$db_name			= "";
				$db_link_id=mysql_pconnect($db["master"]["host"], $db["master"]["user"], $db[master]["pass"]) or die("Unable to connect to SQL 'master'");
		}
		$result=array($db_link_id,$db_name,$random);
	return $result;
	}

	function write_db($query,$fname,$table="",$db="master") {
		list($db_link_id,$db_name)=$this->connect_db($db);
		$this->read_db($db_name,$query,$db_link_id) or die("$fname: ($db)".mysql_error());

		if ($table) {
			$result=mysql_result($this->read_db($db_name, "SELECT LAST_INSERT_ID() FROM $table", $db_link_id),0);
			$query=str_replace("(null","($result",$query);
		}
	return $result;
	}

	function read_db($db_name, $query, $db_link_id) {
               if (!mysql_select_db($db_name, $db_link_id)) {
                       echo 'Could not select database';
                       exit;
               }
               $result = mysql_query($query, $db_link_id);
               if (!$result) {
                       echo "DB Error, could not query the database\n";
                       echo 'MySQL Error: ' . mysql_error();
                       exit;
               }
       return $result;
       }

}
?>