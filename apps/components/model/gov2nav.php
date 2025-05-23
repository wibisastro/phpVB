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

    function setDefaultNavCustom ($_menuFile="") {
        global $pageID,$config,$self;
        $this->sidebar('gov2navMenuCustom.html');
        $GLOBALS['vueData']['pathurl']=$config->webroot."/components/gov2nav/breadcrumb/$pageID/".$self->className."/".str_replace(".xml","",$_menuFile);
    }
	
    function menubar ($pageID,$_menuFile="",$xml=true, $data = array(), $caption = '', $icon = '') {
        global $doc;
        static $_menus=array();
        $_menuFilePath=__DIR__."/../../$pageID/xml/$_menuFile";
        

        if (!$_menuFile) {
            $_menuFilePath=__DIR__."/../../../core/config/menu.".STAGE.".xml";
        } elseif ($_menuFile && !file_exists($_menuFilePath)) {
            unset($_menuFilePath);
        }

        if ($_menuFilePath) {
            if ($xml) {
                $_menu=simplexml_load_file($_menuFilePath, "SimpleXMLElement", LIBXML_NOCDATA);
                $_json=json_decode(json_encode($_menu),TRUE);
            }
            else {
                $_json=$this->menubarNoXml($pageID,$data,$caption,$icon);
            }

            if (!is_array($_json)) $_json = [];
            $_json['label']=$pageID;
            if (!is_array($_json['menu'][0])) {
                $_singleMenu=$_json['menu'];
                unset($_json['menu']);
                $_json['menu'][sizeof($_menus)+1]=$_singleMenu;
            } 
            array_push($_menus,$_json);
            if(is_array($this->collectMenu)) array_push($this->collectMenu,$_json);
            $doc->body['menus'] = $_menus;

            return $_menus;
        } else {
            throw new \Exception('NoNavXMLFile:'.$_menuFile);   
        }
    }

    function menubarNoXmlOld ($pageID, $data, $caption, $icon) {
        global $doc;
        $result = array();
        $menu = array();
        $iterasi = array('Pemilih','KK','TMS','Ganda','Baru','Ubah');
        // create array from query
        if ($data) {
            foreach ($iterasi as $i) {
                $buffer = array();
                $i_lower = strtolower($i);
                foreach ($data as $d) {
                    if ($i == 'Pemilih') {
                        $uri = "/{$pageID}/tahapan/{$d['id']}";
                    }
                    else {
                        $uri = "/{$pageID}/tahapan{$i_lower}/{$d['id']}";
                    }
                    $buffer[] = array('caption'=>$d['tahapan_nama'], 'icon'=>'fa fa-list', 'url'=>$uri);
                }
                $temp = array('caption'=>$i, 'icon'=>$icon, 'menu'=>$buffer);
                array_push($menu, $temp);
            }
            $result['menu'] = $menu;
            return $result;
        } else {
            throw new \Exception('NoDataSent:'.$_menuFile);   
        }   
    }

    function menubarNoXml ($pageID, $data, $caption, $icon) {
        global $doc;
        $result = array();
        $menu = array();
        $iterasi = array('Pemilih','KK','TMS','Ganda','Baru','Ubah');
        // create array from query
        if ($data) {
            foreach ($data as $d) {
                $buffer = array();
                foreach ($iterasi as $i) {
                    $i_lower = strtolower($i);
                    if ($i == 'Pemilih') {
                        $uri = "/{$pageID}/tahapan/{$d['id']}";
                    }
                    else {
                        $uri = "/{$pageID}/tahapan{$i_lower}/{$d['id']}";
                    }
                    $buffer[] = array('caption'=>$i, 'icon'=>'fa fa-list', 'url'=>$uri);
                }
                $temp = array('caption'=>$d['tahapan_nama'], 'icon'=>$icon, 'menu'=>$buffer);
                array_push($menu, $temp);
            }
            
            $result['menu'] = $menu;
            return $result;
        } else {
            throw new \Exception('NoDataSent:'.$_menuFile);   
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