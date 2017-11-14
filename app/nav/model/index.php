<?php namespace App\nav\model;

class index extends \Gov2lib\env\customException {
	function __construct () {
		$this->templateDir=__DIR__."/../view";
		$this->controller=__DIR__."/../index.php";
		$this->baseName="nav";
        $this->baseBody=$this->baseName.'Body.html';
	}
    
    function menubar ($_menuFile="") {
        global $doc,$pageID;
        static $_menus=array();
        $_menuFilePath=__DIR__."/../../$pageID/$_menuFile";
        if (!$_menuFile) {
            $_menuFilePath=__DIR__."/../../../config/menu.".STAGE.".xml";   
        } elseif ($_menuFile && !file_exists($_menuFilePath)) {
            unset($_menuFilePath);
        }
        try {
            if ($_menuFilePath) {
                $_menu = simplexml_load_file($_menuFilePath, "SimpleXMLElement", LIBXML_NOCDATA);
                $_json=json_decode(json_encode($_menu),TRUE);
                array_push($_menus,$_json);
                $doc->body['menus'] = $_menus;
                $this->breadcrumb($_menus,$pageID);
                if (is_array($this->breadcrumb)) {krsort($this->breadcrumb);}
                $doc->body['breadcrumb'] = $this->breadcrumb;
            } else {
                throw new \Exception('NoNavXMLFile:'.$_menuFile);   
            }
        } catch (\Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }
    }
    
    function breadcrumb ($data,$pageID) {
        static $c;
        $c+=0;
        if (is_array($data)) {
            foreach ($data as $child) {
                if ($child["url"] == "/".$pageID) {
                    $c++;
                    $this->breadcrumb[$c]["caption"]=$child["caption"];
                    $this->breadcrumb[$c]["url"]=$child["url"];
                } elseif($child["menu"]) {
                    $b=$c;
                    $this->breadcrumb($child["menu"],$pageID);
                    if ($c>$b) {
                        $c++;
                        $this->breadcrumb[$c]["caption"]=$child["caption"];
                        $this->breadcrumb[$c]["url"]=$child["url"];
                        $c=0;
                        break;
                    }
                }
            }
        }
    }
}
?>