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
    private $tbl;

	function __construct () {
        global $pageID;
		require_once '../vendor/sergeytsalkov/meekrodb/db.class.php';
        \DB::$error_handler = false;
        \DB::$throw_exception_on_error = true;
        \DB::$throw_exception_on_nonsql_error=true;
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
                        $this->tbl = new \stdClass();       
                        $name = (string) $attribute->name[0];
                        $this->tbl->{$name} = $this->tbl->{$name} ?? $table;
                    }
                    //echo "cek =>".$this->tbl->daftar_aset."<br/>"; 
                    //exit;
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
		static $_recentRandom,$config;
        global $pageID;
        if (!$dsnName) {$dsnName="master";}
		$_dsns='../apps/'.$pageID.'/xml/dsnSource.'.STAGE.'.xml';
        //echo $dsnName;
		try {
		    if (file_exists($_dsns)) {
		        $list=simplexml_load_file($_dsns);
				if (is_object($list)) {
                    if ($list->share) {
                        $shared_file='../apps/'.$list->share.'/xml/dsnSource.'.STAGE.'.xml';
                        if (file_exists($shared_file)) {
                            $shared_file_list=simplexml_load_file($shared_file);
                            if (is_object($shared_file_list)) {
                               // print_r($shared_file_list);
                                $_dsn=$this->credentialDB($shared_file_list,$dsnName);
                            } else {
                                throw new \Exception('InvalidDSNShareFile:'.$shared_file);
                            }
                        } else {
                            throw new \Exception('DSNShareFileNotExist:'.$shared_file);
                        }
                    }
                    if (!is_array($_dsn)) {
                        $_dsn=$this->credentialDB($list,$dsnName);
                    }
                    $_link_id=mysqli_connect($_dsn['host'],$_dsn['user'],$_dsn['pass'],$_dsn['db'],$_dsn['port']);

					if ($_link_id) {
						$result=array($_link_id,$_dsn['db'],$_recentRandom);
						return $result;
					} else {
						throw new \Exception('CannotConnectDSN:'.mysqli_connect_error()." (dsnSource $dsnName)");
                    }
				}  else {
                    libxml_use_internal_errors(true);
                    foreach(libxml_get_errors() as $error) {
                        $_invalidXml.=$error->message;
                    }

			        throw new \Exception('InvalidDSNConfigFile:'.$_invalidXml);
				}
		    } else {
		        throw new \Exception('NoDSNConfigFile:'.$_dsns);
		    }
		} catch (\Exception $e) {
            $this->exceptionHandler($e->getMessage());
        }

	}

    function credentialDB ($list,$dsnName) {
        global $config;
        foreach ($list->dsn as $dsn) {
            if ($dsnName==trim($dsn->name)) {
                $this->dsnName=$dsnName;
                $_result['user']=trim($dsn->user);
                $_result['pass']=trim($dsn->pass);
                $_result['host']=trim($dsn->host);
                if ($dsn->port) {
                    $_result['port']=trim($dsn->port);
                } else {
                    $_result['port']="3306";
                }

                $_result['db']=trim($dsn->db);

//                $config->db_host=$_host;

                \DB::$user = trim($dsn->user);
                \DB::$password = trim($dsn->pass);
                \DB::$dbName = trim($dsn->db);
                \DB::$host = trim($dsn->host);
                \DB::$connect_options = array(MYSQLI_CLIENT_COMPRESS => true);
                if ($dsn->port) {
                    \DB::$port = trim($dsn->port);
                } else {
                    \DB::$port = "3306";
                }
                return $_result;
                break;
            }
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

    function connectAPI ($dsnName="master") {
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
                            if (is_object($shared_file_list)) {
                                $_dsn=$this->credentialAPI($shared_file_list,$dsnName);
                            } else {
                                throw new \Exception('InvalidDSNShareFile:'.$shared_file);
                            }
                        } else {
                            throw new \Exception('DSNShareFileNotExist:'.$shared_file);
                        }
                    }
                    if (!is_array($_dsn)) {
                        $_dsn=$this->credentialAPI($list,$dsnName);
                    }
                    $this->api=$_dsn;
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

    function credentialAPI ($list,$dsnName) {
        global $config;
        foreach ($list->dsn as $dsn) {
            if ($dsnName==trim($dsn->name)) {
                $this->dsnName=$dsnName;
                $_result['user']=trim($dsn->user);
                $_result['pass']=trim($dsn->pass);
                $_result['host']=trim($dsn->host);
                if ($dsn->port) {
                    $_result['port']=trim($dsn->port);
                } else {
                    $_result['port']="442";
                }
                $_result['db']=trim($dsn->db);
                return $_result;
                break;
            }
        }
    }
}
?>