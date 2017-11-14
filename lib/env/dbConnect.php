<?php namespace Gov2lib\env;
/*
Author		: Wibisono Sastrodiwiryo
Date		: 29 Sep 2017
Copyright	: eGov Lab UI 
Contact		: wibi@cybergl.co.id
Version		: 0.1.0 --- initial release
			0.2.0 --- PSR 4
*/

class dbConnect extends customException {
	function __construct () {
		$tables=__DIR__.'/../../config/dbTables.xml';
		try {
		    if (file_exists($tables)) {
		        $list=simplexml_load_file($tables);
				if (is_object($list)) {
					foreach ($list->table as $table) {
						$this->{'tbl_'.$table}=$table;
					}
				} else {
			        throw new \Exception('InvalidTableConfigFile');
				}
		    } else {
		        throw new \Exception('NoTableConfigFile');
		    }
		} catch (\Exception $e) {
			$this->exceptionHandler($e->getMessage());
		}	
	}
  
	function connectDB ($dsnName="master") {
		static $_recentRandom;
		$_dsns=__DIR__.'/../../config/dbSource.xml';
		try {
		    if (file_exists($_dsns)) {
		        $list=simplexml_load_file($_dsns);
				if (is_object($list)) {
					foreach ($list->dsn as $dsn) {
						if ($dsnName==$dsn->name) {
							$_user=$dsn->user;
			                $_pass=$dsn->pass;
			                $_host=$dsn->host;
			                $_db=$dsn->db;	
						}
					}
					$_dbLink=mysqli_connect($_host, $_user, $_pass,$_db);
					if ($_dbLink) {
						$result=array($_dbLink,$_db,$_recentRandom);
						return $result;
					} else {
						throw new \Exception('CannotConnectDSN '.$dsnName);
                    }
				}  else {
			        throw new \Exception('InvalidDSNConfigFile');
				}
		    } else {
		        throw new \Exception('NoDSNConfigFile');
		    }
		} catch (\Exception $e) {
            $this->exceptionHandler($e->getMessage());
        }	
        
	}

	function writeDB ($query,$fname,$table="",$dsnName="master") {
		try {
			list($_link_id,$db_name)=$this->connectDB($dsnName);
			if ($_link_id) {
				$this->queryDB($db_name,$query,$_link_id);
				if (strlen($table)>0) {
					$result=mysqli_fetch_object($this->queryDB($db_name, "SELECT LAST_INSERT_ID() AS id FROM $table",$_link_id));
					return $result->id;
				}
			} else {
				throw new \Exception('DBLinkError:'.mysqli_error($_link_id));
			}
		} catch (\Exception $e) {
			$this->exceptionHandler($e->getMessage());
		}
		
		
	}

	function queryDB ($db_name, $query, $_link_id) {
		try {
			$result = mysqli_query($_link_id,$query);
			if ($result) {return $result;}
			else {throw new \Exception('DBQueryError:'.mysqli_error($_link_id));}
		} catch (\Exception $e) {
			$this->exceptionHandler($e->getMessage());
		}
	}
	
}
?>