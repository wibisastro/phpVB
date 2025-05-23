<?php namespace App\survey;
    
class index extends \Gov2lib\api {

    public $IS_DEV;
    function __construct() {
        global $self;
        parent::__construct();
		$self->takeAll("components");
        $self->takeAll("rokuone");
        if (isset($self->ses->val['account_id'])) {
            $self->ses->authenticate('member');    
        }
        else {
            $self->ses->authenticate('public');
        }
        $self->scrollInterval=100;
        // $self->ses->authenticate('maintenance','5.30 AM');
        $this->IS_DEV = strpos($_SERVER['SERVER_NAME'], 'bkn.go.id') === false;
    }
    
    function index() {
        global $self,$doc;
        $self->gov2nav->setDefaultNavCustom();
        $doc->body("pageTitle",'KLN');
        $doc->body("subTitle",'Survey');
        $GLOBALS['vueData']['bgImg'] = '';
        $self->content();
    }
    
    function version() {
        global $self,$doc;
        $self->gov2nav->setDefaultNavCustom();
        $doc->body("pageTitle",'phpVB Skeleton');
        $doc->body("subTitle",'Versioning');
        $doc->body("appName",'Gov2.0 SSO');
        $self->content('version.html');
    }

    function breadcrumb ($vars) {
        global $self,$config,$doc;
        $xml = "";
        if ($vars['xml']) {$xml=$vars['xml'].".xml";}
        $self->menus=$self->menubar($vars['pageID'],$xml);
        $self->breadcrumb($self->menus,$vars['pageID'],$vars['className']);
        if (!$doc->error) {
            krsort($self->breadcrumb);
            $c=1;
            $url=json_decode(json_encode($config->webroot),true);
            $data[0]=array("caption"=>"Home","url"=>$url[0]);
            foreach($self->breadcrumb as $key => $val) {
                if ($val['caption']) {
                    $data[$c]=$val;
                    $c++;
                }
            }
            $response=$data;
        } else {
            $response=$doc->response("is-danger");
            header("HTTP/1.1 422 Read XML Fails");
        }
        return $response;
    }

    function getHeaders() {
        $_header =  simplexml_load_file(__DIR__."/xml/header.xml");
        return json_encode($_header);
    }

    function getMenus() {
        global $self, $pageID;

        $dsn_id = (int)$self->dsn_id;
        $dsn    = (string)$self->dsn;

        if ($this->IS_DEV) {
            $keuangan   = 'keuanganbiro.bkn.kl2.web.id';
            $etravel    = 'etravel.gov2.web.id';
            $rokuone    = 'rokuone.gov2.web.id';
            $rosdm      = 'rosdm.bkn.kl2.web.id';
            $kerjasama  = 'kerjasama.gov2.web.id';
            $honor      = 'honor.gov2.web.id';
            $sikap      = 'sikap.gov2.web.id';
            $sipepi     = 'sipepi.gov2.web.id';
        }else{
            $keuangan   = 'keuanganbiro.bkn.go.id';
            $etravel    = 'etravel.bkn.go.id';
            $rokuone    = 'keuangan.bkn.go.id';
            $rosdm      = 'rosdm.bkn.go.id';
            $kerjasama  = 'kerjasama.bkn.go.id';
            $honor      = 'honor.bkn.go.id';
            $sikap      = 'sikap.bkn.go.id';
            $sipepi     = 'sipepi.bkn.go.id';
        }
        
        if($kerjasama == $_SERVER['SERVER_NAME'] && $dsn == 'hhkbiro'){

            $self->gov2nav->menubar('refbkn', 'menu_kerjasama.xml');
            $self->gov2nav->menubar('kdn', 'menu_kerjasama_hhkbiro.xml');
            $response = $self->gov2nav->menubar('kln', 'menu_kerjasama_hhkbiro.xml');

        }else if($kerjasama == $_SERVER['SERVER_NAME'] && $dsn == 'sesma'){

            $self->gov2nav->menubar('refbkn', 'menu_kerjasama.xml');
            $self->gov2nav->menubar('kdn', 'menu_kerjasama_unit.xml');
            $response = $self->gov2nav->menubar($pageID, 'menu_kerjasama_sesma.xml');
        
        }else if($kerjasama == $_SERVER['SERVER_NAME'] && $dsn == 'bkn.kl'){

            $self->gov2nav->menubar('refbkn', 'menu_kerjasama.xml');
            $self->gov2nav->menubar('kdn', 'menu_kerjasama_unit.xml');
            $response = $self->gov2nav->menubar($pageID, 'menu_kerjasama_kepala.xml');
        
        }else if($kerjasama == $_SERVER['SERVER_NAME'] && $dsn == 'sdmbiro'){

            $self->gov2nav->menubar('refbkn', 'menu_kerjasama.xml');
            $self->gov2nav->menubar('kdn', 'menu_kerjasama_unit.xml');
            $response = $self->gov2nav->menubar($pageID, 'menu_kerjasama_birosdm.xml');
            
        }else if($kerjasama == $_SERVER['SERVER_NAME'] && $dsn_id > 0){

            $self->gov2nav->menubar('refbkn', 'menu_kerjasama.xml');
            $self->gov2nav->menubar('kdn', 'menu_kerjasama_unit.xml');
            $response = $self->gov2nav->menubar($pageID, 'menu_kerjasama_unit.xml');
            
        }else if($kerjasama == $_SERVER['SERVER_NAME']){

            $self->gov2nav->menubar('refbkn', 'menu_kerjasama.xml');
            $response = $self->gov2nav->menubar('kdn', 'menu_kerjasama.xml');
        
        }else{
            $response = $self->gov2nav->menubar($pageID, 'menu.xml');
        }
        return $response;
    }

    function getPageroles() {
        $path = __DIR__.'/xml/pageroles.xml';
        if (file_exists($path)) {
            return json_encode(simplexml_load_file($path));
        }
        return 0;
    }
}