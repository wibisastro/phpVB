<?php namespace Gov2lib;
/*
Author		: Wibisono Sastrodiwiryo
Date		: 29 Sep 2017
Copyright	: eGov Lab UI 
Contact		: wibi@cybergl.co.id
Version		: 0.1.0 --- initial release
			0.2.0 --- PSR 4
*/
class dsnSource extends document {
	function __construct () {
        global $pageID;
		require_once '../vendor/sergeytsalkov/meekrodb/db.class.php';
        \DB::$error_handler = false;
        \DB::$throw_exception_on_error = true;
		$tables='../apps/'.$pageID.'/xml/dbTables.xml';
		try {
		    if (file_exists($tables)) {
		        $list=simplexml_load_file($tables);
				if (is_object($list)) {
                    if ($list->share) {
                        $shared_file='../apps/'.$list->share.'/xml/dbTables.xml';
                        if (file_exists($shared_file)) {
                            $shared_file_list=simplexml_load_file($shared_file);
                            if (is_object($list)) {
                                $list=$shared_file_list;
                            } else {
                                throw new \Exception('InvalidTableShareFile:'.$shared_file);       
                            }
                        } else {
                            throw new \Exception('TableShareFileNotExist:'.$shared_file);
                        }
                    } 
                    foreach ($list->table as $table) {
                        $attribute = $table->attributes();
                        $this->tbl->{$attribute->name[0]}=$table;
                    }
				} else {
			        throw new \Exception('InvalidTableConfigFile:'.$tables);
				}
		    } else {
		        throw new \Exception('TableConfigFileNotExist:'.$tables);
		    }
		} catch (\Exception $e) {
			$this->exceptionHandler($e->getMessage());
		}	
	}
  
	function connectDB ($dsnName="master") {
		static $_recentRandom;
        global $pageID;
        if (!$dsnName) {$dsnName="master";} 
		$_dsns='../apps/'.$pageID.'/xml/dsnSource.'.STAGE.'.xml';
		try {
		    if (file_exists($_dsns)) {
		        $list=simplexml_load_file($_dsns);
				if (is_object($list)) {
                    if ($list->share) {
                        $shared_file='../apps/'.$list->share.'/xml/dsnSource.'.STAGE.'.xml';
                        if (file_exists($shared_file)) {
                            $shared_file_list=simplexml_load_file($shared_file);
                            if (is_object($list)) {
                                $list=$shared_file_list;
                            } else {
                                throw new \Exception('InvalidDSNShareFile:'.$shared_file);       
                            }
                        } else {
                            throw new \Exception('DSNShareFileNotExist:'.$shared_file);
                        }                        
                    }
					foreach ($list->dsn as $dsn) {
						if ($dsnName==$dsn->name) {
                            $this->dsnName=$dsnName;
							$_user=$dsn->user;
			                $_pass=$dsn->pass;
			                $_host=$dsn->host;
			                $_db=$dsn->db;	
							
							\DB::$user = $dsn->user;
							\DB::$password = $dsn->pass;
							\DB::$dbName = $dsn->db;
							\DB::$host = $dsn->host;
						}
					}
					$_link_id=mysqli_connect($_host, $_user, $_pass,$_db);
					if ($_link_id) {
						$result=array($_link_id,$_db,$_recentRandom);
						return $result;
					} else {
						throw new \Exception('CannotConnectDSN:'.$dsnName);
                    }
				}  else {
			        throw new \Exception('InvalidDSNConfigFile:'.$_dsns);
				}
		    } else {
		        throw new \Exception('NoDSNConfigFile:'.$_dsns);
		    }
		} catch (\Exception $e) {
            $this->exceptionHandler($e->getMessage());
        }	
        
	}

	function writeDB ($query,$fname,$table="") {
		try {
			list($_link_id,$db_name)=$this->connectDB($this->dsnName);
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
    
    function scroll ($scroll=0){
        global $self;
        $scroll--;
        if ($this->scrollInterval) {$_interval=$this->scrollInterval;} 
        else {$_interval=1000;}
		$scroll=$scroll*$_interval;
    return "$scroll,$_interval";
    }
	
}
?>