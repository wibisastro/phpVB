<?php namespace App\components\model;

class gov2nav extends \Gov2lib\document {

	function __construct () {
        global $pageID,$self,$config,$cmdID;
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $className=$self->className;
        if (!$className) {$className=$this->className;}
        $base=rtrim($config->webroot."/components/gov2nav/breadcrumb/$pageID/$className", '/');
        $GLOBALS['vueData']['pathurl']=$cmdID ? $base.'?cmdID='.urlencode($cmdID) : $base;
	}

	function dependencies () {
	}

	function setDefaultNav ($_menuFile="") {
        global $pageID,$config,$self,$doc,$cmdID;
        // Accumulate menu data (boleh dipanggil berkali-kali untuk multi-app)
        $this->menus=$this->menubar($pageID,$_menuFile);
        // Register sidebar template hanya 1x — pakai substring match agar
        // tahan terhadap variasi prefix module (@components/ vs raw filename)
        if (!$this->_alreadyRegistered($doc->sidebar ?? [], 'gov2navMenu.html')) {
            $this->sidebar('gov2navMenu.html');
        }
        // Register breadcrumb template hanya 1x
        if (!$this->_alreadyRegistered($doc->content ?? [], 'gov2navBreadcrumb.html')) {
            $this->content('gov2navBreadcrumb.html');
        }
        // Set pathurl HANYA jika menu yang baru di-load berisi URL halaman aktif.
        // Mencegah last-call-wins masalah saat controller call setDefaultNav
        // berkali-kali dengan menu file berbeda (mis. SAKIPAI: ingest + aisakip).
        $lastMenu = end($this->menus);
        if ($this->_menuContainsActiveUrl($lastMenu, $pageID, $self->className, $cmdID)) {
            $base = rtrim($config->webroot."/components/gov2nav/breadcrumb/$pageID/".$self->className."/".str_replace(".xml","",$_menuFile), '/');
            $GLOBALS['vueData']['pathurl'] = $cmdID ? $base.'?cmdID='.urlencode($cmdID) : $base;
        }
	}

    function setCustomNav ($_menuFile="") {
        global $pageID,$config,$self,$doc,$cmdID;
        // Cek di $doc langsung agar tahan terhadap multi-instance via take()
        if (!$this->_alreadyRegistered($doc->sidebar ?? [], 'gov2navMenuCustom.html')) {
            $this->sidebar('gov2navMenuCustom.html');
        }
        $base = rtrim($config->webroot."/components/gov2nav/breadcrumb/$pageID/".$self->className."/".str_replace(".xml","",$_menuFile), '/');
        $GLOBALS['vueData']['pathurl'] = $cmdID ? $base.'?cmdID='.urlencode($cmdID) : $base;
    }

    private function _alreadyRegistered($haystack, $needle) {
        // Substring match agar tahan variasi format penyimpanan
        // (mis. '@components/gov2navMenu.html' atau raw 'gov2navMenu.html')
        foreach ((array)$haystack as $entry) {
            if (is_string($entry) && str_contains($entry, $needle)) {
                return true;
            }
        }
        return false;
    }

    // Recursive walk: cek apakah node menu (atau descendant-nya) berisi URL
    // halaman aktif. Match level-2 (/pageID/className), level-3
    // (/pageID/className/cmdID), atau homepage (/pageID saat className=index).
    private function _menuContainsActiveUrl($node, $pageID, $className, $cmdID="") {
        if (!is_array($node)) return false;
        $url = $node['url'] ?? null;
        if ($url !== null) {
            if ($url === "/$pageID/$className") return true;
            if ($cmdID && $url === "/$pageID/$className/$cmdID") return true;
            if ($className === 'index' && $url === "/$pageID") return true;
        }
        if (isset($node['menu']) && is_array($node['menu'])) {
            foreach ($node['menu'] as $child) {
                if (is_array($child) && $this->_menuContainsActiveUrl($child, $pageID, $className, $cmdID)) {
                    return true;
                }
            }
        }
        return false;
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
    
    function breadcrumb ($_data,$_pageID,$_className="",$_cmdID="") {
        static $_c;
        global $config;
        $_c+=0;
        if (is_array($_data)) {
            foreach ($_data as $_child) {
                $url = $_child["url"] ?? null;
                // Saat cmdID di-set, match HANYA level-3 leaf agar walker turun
                // ke submenu (jadi level-2 ancestor di-add via bubble, bukan
                // direct match yang akan stop di level-2).
                $isLeaf = $_cmdID
                    ? ($url === "/$_pageID/$_className/$_cmdID")
                    : ($url === "/$_pageID/$_className" || ($_className === "index" && $url === "/$_pageID"));
                if ($isLeaf) {
                    $_c++;
                    $this->breadcrumb[$_c]["caption"]=$_child["caption"];
                    $this->breadcrumb[$_c]["url"]=$config->webroot.$url;
                } elseif (!empty($_child["menu"])) {
                    $_b=$_c;
                    $this->breadcrumb($_child["menu"],$_pageID,$_className,$_cmdID);
                    if ($_c>$_b) {
                        $_c++;
                        $this->breadcrumb[$_c]["caption"]=$_child["caption"];
                        $this->breadcrumb[$_c]["url"]=$config->webroot.$url;
                        $_c=0;
                        break;
                    }
                }
            }
        }
    }
}
?>