<?php namespace Gov2lib;

/* -----------------------------------------------------------
 *
 * This class is intended to be used only for a temporary
 * DB connection instance, to provide an easy DB access which
 * work with the existing system.
 *
 * for more details: rijal@cybergl.co.id
 *
 * -----------------------------------------------------------
 */

class DBConnector
{
    private $dsn;
    public $db;
    public function __construct($dsn = 'master')
    {
        $this->dsn = trim($dsn);
        $this->initialize_dsn();
        $this->initialize_db();
    }

    private function initialize_dsn()
    {
        global $pageID, $doc;
        $_dsns='../apps/'.$pageID.'/xml/dsnSource.'.STAGE.'.xml';
        try {
            if (file_exists($_dsns)) {
                $list = simplexml_load_file($_dsns);
                if (is_object($list)) {
                    if ($list->share) {
                        $shared_file = '../apps/' . $list->share . '/xml/dsnSource.' . STAGE . '.xml';
                        if (file_exists($shared_file)) {
                            $shared_file_list = simplexml_load_file($shared_file);
                            if (is_object($shared_file_list)) {
                                $list = $shared_file_list;
                            } else {
                                throw new \Exception('InvalidDSNShareFile:' . $shared_file);
                            }
                        } else {
                            throw new \Exception('DSNShareFileNotExist:' . $shared_file);
                        }
                    }
                } else {
                    libxml_use_internal_errors(true);
                    $_invalidXml = "";
                    foreach(libxml_get_errors() as $error) {
                        $_invalidXml .= $error->message;
                    }
                    throw new \Exception('InvalidDSNConfigFile:'.$_invalidXml);
                }
            } else {
                throw new \Exception('NoDSNConfigFile:'.$_dsns);
            }

            $dsn_properties = [];

            foreach ($list->dsn as $dsn) {
                if (trim($this->dsn) === trim($dsn->name)) {
                    $dsn_properties['name'] = trim($dsn->name);
                    $dsn_properties['user'] = trim($dsn->user);
                    $dsn_properties['pass'] = trim($dsn->pass);
                    $dsn_properties['host'] = trim($dsn->host);
                    $dsn_properties['db'] = trim($dsn->db);
                    $dsn_properties['connect_options'] = array(MYSQLI_CLIENT_COMPRESS => true);
                    if (!$dsn->port) {
                        $dsn_properties['port'] = 3306;
                    } else {
                        $dsn_properties['port'] = $dsn->port;
                    }
                    $this->dsn = $dsn_properties;
                    break;
                }
            }
        } catch (\Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }
    }

    public function initialize_db()
    {
        global $doc;
        try {
            $this->db = new \MeekroDB(
                $this->dsn['host'],
                $this->dsn['user'],
                $this->dsn['pass'],
                $this->dsn['db'],
                $this->dsn['port']);
            $this->db->connect_options = $this->dsn['connect_options'];
        } catch (\MeekroDBException $e) {
            $doc->exceptionHandler($e->getMessage());
        }
    }
}