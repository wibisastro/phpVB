<?php namespace App\components\model;

class gov2nav extends \Gov2lib\document {

	function __construct () {
        global $pageID,$self,$config,$cmdID;
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $className=$self->className;
        if (!$className) {$className=$this->className;}
        // Hanya set default pathurl bila belum di-set. Mencegah multi-take()
        // (mis. SAKIPAI yang call setDefaultNav 3x dengan menu berbeda) meng-overwrite
        // pathurl yang benar dari setDefaultNav guard sebelumnya.
        if (!isset($GLOBALS['vueData']['pathurl'])) {
            $base=rtrim($config->webroot."/components/gov2nav/breadcrumb/$pageID/$className", '/');
            $GLOBALS['vueData']['pathurl']=$cmdID ? $base.'?cmdID='.urlencode($cmdID) : $base;
        }
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

    // Recursive walk: cek apakah node menu (atau descendant-nya) "milik" app yang
    // sedang aktif. Pattern match (urutan dari spesifik ke umum):
    //   - /pageID/className/cmdID  (level-3 leaf exact)
    //   - /pageID/className        (level-2 exact)
    //   - /pageID                  (homepage saat className=index)
    //   - /pageID/...              (prefix — menu berisi entry untuk app ini,
    //                               cukup untuk anggap "owner" menu app index page
    //                               yang tidak ada URL exact-match-nya, mis. /aisakip)
    private function _menuContainsActiveUrl($node, $pageID, $className, $cmdID="") {
        if (!is_array($node)) return false;
        $url = $node['url'] ?? null;
        if ($url !== null) {
            if ($url === "/$pageID/$className") return true;
            if ($cmdID && $url === "/$pageID/$className/$cmdID") return true;
            if ($className === 'index' && $url === "/$pageID") return true;
            if (str_starts_with($url, "/$pageID/")) return true;
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
    
    // Depth-first walker: build breadcrumb path dengan deepest-match-wins.
    // - Selalu recurse ke children (bahkan setelah parent match) — supaya leaf
    //   yang lebih spesifik bisa replace parent (kasus parent+child URL sama).
    // - Path order: root → leaf (no krsort needed di endpoint).
    // - Skip item tanpa caption dari ancestors (mis. wrapper dari menubar).
    function breadcrumb ($_data,$_pageID,$_className="",$_cmdID="",$_ancestors=[]) {
        global $config;
        if (!is_array($_data)) return;
        foreach ($_data as $_child) {
            if (!is_array($_child)) continue;
            $url = $_child["url"] ?? null;
            $caption = $_child["caption"] ?? null;
            $newAncestors = ($caption !== null)
                ? array_merge($_ancestors, [["caption"=>$caption, "url"=>$config->webroot.($url ?? "")]])
                : $_ancestors;

            $isMatch = false;
            if ($url !== null) {
                if ($_cmdID && $url === "/$_pageID/$_className/$_cmdID") $isMatch = true;
                elseif (!$_cmdID && $url === "/$_pageID/$_className") $isMatch = true;
                elseif (!$_cmdID && $_className === "index" && $url === "/$_pageID") $isMatch = true;
            }
            if ($isMatch) {
                $this->breadcrumb = $newAncestors;
            }

            if (!empty($_child["menu"])) {
                $this->breadcrumb($_child["menu"],$_pageID,$_className,$_cmdID,$newAncestors);
            }
        }
    }
}
?>