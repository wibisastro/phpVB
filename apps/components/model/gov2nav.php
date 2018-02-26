<?php namespace App\components\model;

class gov2nav extends \Gov2lib\document {
	function __construct () {
        global $pageID,$self,$config;
		$this->templateDir=__DIR__."/../view";        
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $className=$self->className;
        if (!$className) {$className=$this->className;}
        $GLOBALS['vueData']['pathurl']=$config->webroot."/components/gov2nav/breadcrumb/$pageID/$className";
	}

	function setDefaultNav ($_menuFile="") {
        global $pageID,$config,$self;
        $this->menus=$this->menubar($pageID,$_menuFile);
        $this->sidebar('gov2navMenu.html');
        $this->content('gov2navBreadcrumb.html');
        $GLOBALS['vueData']['pathurl']=$config->webroot."/components/gov2nav/breadcrumb/$pageID/".$self->className."/".str_replace(".xml","",$_menuFile);
	}
	
    function menubar ($pageID,$_menuFile="") {
        global $doc;
        static $_menus=array();
        $_menuFilePath=__DIR__."/../../$pageID/xml/$_menuFile";
        if (!$_menuFile) {
            $_menuFilePath=__DIR__."/../../../core/config/menu.".STAGE.".xml";
        } elseif ($_menuFile && !file_exists($_menuFilePath)) {
            unset($_menuFilePath);
        }
        if ($_menuFilePath) {
            $_menu = simplexml_load_file($_menuFilePath, "SimpleXMLElement", LIBXML_NOCDATA);
            $_json=json_decode(json_encode($_menu),TRUE);
            array_push($_menus,$_json);
            $doc->body['menus'] = $_menus;
            return $_menus;
        } else {
            throw new \Exception('NoNavXMLFile:'.$_menuFile);   
        }
    }
    
    function breadcrumb ($_data,$_pageID,$_className="") {
        static $_c;
        global $config;
        $_c+=0;
        if (is_array($_data)) {
            foreach ($_data as $_child) {
                if ($_child["url"] == "/".$_pageID."/".$_className || ($_className == "index" && $_child["url"] == "/".$_pageID)) {
                    $_c++;
                    $this->breadcrumb[$_c]["caption"]=$_child["caption"];
                    $this->breadcrumb[$_c]["url"]=$config->webroot.$_child["url"];
                } elseif ($_child["menu"]) {
                    $_b=$_c;
                    $this->breadcrumb($_child["menu"],$_pageID,$_className);
                    if ($_c>$_b) {
                        $_c++;
                        $this->breadcrumb[$_c]["caption"]=$_child["caption"];
                        $this->breadcrumb[$_c]["url"]=$config->webroot.$_child["url"];
                        $_c=0;
                        break;
                    }
                }
            }
        }
    }
}
?>