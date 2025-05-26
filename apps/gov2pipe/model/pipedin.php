<?php namespace App\gov2pipe\model;
use DB;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;

class pipedin extends \Gov2lib\crudHandler {
	function __construct () {
		global $config, $doc;
		$this->templateDir= __DIR__ . "/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $this->controller= __DIR__ . "/renjakl/" .$this->className.".php";
        try {
            $cookies = $doc->envRead($_COOKIE['Gov2Session']);
            $this->dsn = $cookies['portal'];
            $this->dsn_id = $cookies['portal_id'];
            if (!$this->dsn) {
                $this->dsn      = $config->domain->attr['dsn'];
                $this->dsn_id   = $config->domain->attr['id'];
            }
        } catch (\Exception $e) {
            $this->dsn      = $config->domain->attr['dsn'];
            $this->dsn_id   = $config->domain->attr['id'];
        }
        parent::__construct($this->dsn);
	}

	function openPipe ($url) {
	    global $self,$doc;
        $jar = new \GuzzleHttp\Cookie\SessionCookieJar('PHPSESSID', true);
        $this->client = new \GuzzleHttp\Client(['cookies' => $jar]);
        try {
            $res = $this->client->request('GET', $url,[
                'cookies' => $jar
            ]);
            if ($res) {
                $_response=explode(PHP_EOL,$res->getBody());
                foreach($_response as $_line) {
                    if (strstr($_line,"setUserId")) {
                        list($_key,$_val)=explode(",",$_line);
                        $_email=str_replace(" \"","",$_val);
                        $_email=str_replace("\"]);","",$_email);
                        break;
                    }
                }
                $_SESSION['pipedin']['account']=$_email;
                $_response=$_email;
            } else {
                unset($_SESSION['pipedin']);
                throw new \Exception('GetFail:'.$url);
            }
        } catch (ClientException $e) {
            $doc->error("RequestError",Psr7\str($e->getRequest()));
            $doc->error("ResponseError",Psr7\str($e->getResponse()));
        } catch (\Exception $e) {
            $doc->error("Error",$e->getRequest());
        } 
        return $_response;
    }
    
    function dependencies () {
        
    }

    
}
?>