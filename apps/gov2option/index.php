<?php namespace App\gov2option;
    
class index extends \Gov2lib\api {
    function __construct() {
        global $self;
        parent::__construct();
		$self->takeAll("components");
//        $self->take("apiwilayah","widget");
        $self->ses->authenticate('guest');
        $self->scrollInterval=100;
        $this->DEBUG = !strpos($_SERVER['SERVER_NAME'], 'kpu.go.id');
        // $self->ses->authenticate('maintenance','5.30 AM');
    }
    
    function index() {
        global $self,$doc,$config;
        $self->gov2nav->setDefaultNavCustom();
        $self->gov2notification->content();
        $doc->body("pageTitle",'Sidalih');
        $doc->body("subTitle",'SISTEM INFORMASI DAFTAR PEMILIH');
        $pilkada = $self->timeline();
        if($pilkada){
            $self->content();
        }else{
            $self->content('sidalih3.html');
        }
        /*
        if ($self->ses->val['account_id']=='14') {
            print_r($self->ses);
            print_r($config->domain->attr);
        }
        */
    }

    function sidalih3(){
        global $self, $doc;
        $self->gov2nav->setDefaultNavCustom();
        $self->gov2notification->content();
        $doc->body("pageTitle",'Sidalih');
        $doc->body("subTitle",'SISTEM INFORMASI DAFTAR PEMILIH');
        $self->content('sidalih3.html');
    }
    
    function version() {
        global $self,$doc;
        $self->gov2nav->setDefaultNavCustom();
        $self->gov2notification->content();
        $doc->body("pageTitle",'Sidalih');
        $doc->body("subTitle",'Versioning');
        $self->content('version.html');
    }

    function getList() {
        global $self, $doc;
        $data = $self->getList();
        return $doc->responseGet($data);
    }
    
    function listWilayah($vars) {
        global $self;
        $response=$self->widget->getRecords($vars);
        return $response;
    }

    function getKel($vars) {
        global $self,$config;
        $data=$self->widget->getBreadcrumb("INDONESIA");
        $self->ses->val['filter_RW']="";
        $self->ses->val['filter_RT']="";
        $self->ses->val['filter_TPS']="";
        $self->ses->val['filter_Status']="";
        $self->ses->sesSave($self->ses->val);
        if ($data[sizeof($data)-1]['level']==4) {
            $response=$data[sizeof($data)-1];
        } else {
            $response=array("data"=>"empty");
        }
        return $response;
    }
    
    function getWilayah($vars) {
        global $self,$config;
        $data=$self->widget->getBreadcrumb("INDONESIA");
        $self->ses->val['filter_Status']="";
        $self->ses->val['filter_Ket']="";
        $self->ses->sesSave($self->ses->val);
        if ($data[sizeof($data)-1]['level']>1) {
            $response=$data[sizeof($data)-1];
        } else {
            $response=array("data"=>"empty");
        }
        return $response;
    }
    
    function breadcrumb($vars) {
        global $self,$config;
        $response=$self->widget->getBreadcrumb("INDONESIA");
        if ($response[sizeof($response)-1]['level']<2) {
            $_id=trim($config->domain->attr['id']);
            $response=$self->widget->getBreadcrumb("INDONESIA",$_id);
        }
        return $response;
    }
    
    function getTable($vars) {
        global $self,$config;
        //-----sementara baca dummy
        // readfile(__DIR__."/json/dashboard.json");
        //-----kirim request webservice ke kpu.kl2.web.id/boardsidalih
        $_url="https://kpu.kl2.web.id/boardsidalih/sizerows/readTable/";
        $_response = $this->getdata($_url.$config->domain->attr['id']);
        return $_response;
    }

    function barPemilu(){
        global $self;
        $pemilu_id = $self->ses->val['pemilu_id'];
        $_domain = $this->DEBUG ? 'https://kpu.kl2.web.id' : 'https://tahapan.kpu.go.id';
        $_url = join("/", [$_domain,'apitahapan','enrollment','pemiluById']);
        $_response = $this->getdata($_url."/".$pemilu_id);
        return $_response;
    }

    function pilihPemilu($vars){
        global $self, $config, $pageID;
        $domain = $config->domain->attr['dsn'];
        $_url = join("/", ['https://'.$domain, $pageID]);
        $self->ses->val['pemilu_id']=$vars['id'];
        $self->ses->sesSave($self->ses->val);
        header("Location: $_url");
    }

    function addHeader($data, $addition) {
        global $config, $pageID;
        $items = array();
        foreach ($addition as $add) {
            if($config->domain->attr['dsn'] == 'kpu.kl2.web.id' || $config->domain->attr['dsn'] == 'wilayah.kpu.go.id' || 
            $config->domain->attr['dsn'] == 'tahapan.kpu.go.id' || $config->domain->attr['dsn'] == 'api.kpu.go.id'){
                $temp = array('caption' => $add['nama'], 'icon' => 'fa fa-archive', 'uri' => '/'.$pageID);
            }else{
                $temp = array(
                    'caption' => $add['tahapan_nama'], 
                    'icon' => 'fa fa-archive', 
                    'uri' => '/'.$pageID.'/'.'index'.'/'.'pilihPemilu'.'/'.$add['id']
                );
            }
            array_push($items, (object) $temp);
        }
        $result = array();
        $result['caption'] = 'Pemilu';
        $result['items'] = $items;

        array_push($data->header, (object) $result);

        return $data;
    }

    #---coded by claude
    function getYearOptions($vars) {
        global $self, $doc;
        $pageID = $vars['id'] ?? ($_GET['pageID'] ?? '');
        $data = $self->getYearOptions($pageID);
        return $doc->responseGet($data);
    }

    #---coded by claude
    function setYear($vars) {
        global $self, $doc;
        $pageID = $vars['pageID'] ?? ($_POST['pageID'] ?? '');
        $year   = $vars['year']   ?? ($_POST['year']   ?? '');
        if ($year) {
            $self->setYear($pageID, $year);
        }
        return $doc->responseGet(['status' => 'ok', 'year' => $year]);
    }

    #---coded by claude
    function getPageroles($vars = []) {
        $appID = $vars['id'] ?? '';
        if ($appID) {
            $path = __DIR__.'/../'.$appID.'/xml/pageroles.xml';
        } else {
            $path = __DIR__.'/xml/pageroles.xml';
        }
        if (file_exists($path)) {
            return json_encode(simplexml_load_file($path));
        }
        return 0;
    }

    #---coded by claude
    function getUnitKerjaList($vars = []) {
        global $self, $doc;
        $parentId = (int)($vars['id'] ?? 0);
        $data = $self->getUnitKerjaList($parentId);
        return $doc->responseGet($data);
    }

    #---coded by claude
    function searchUnitKerja($vars = []) {
        global $self, $doc;
        $keyword = $_GET['q'] ?? '';
        $data = $self->searchUnitKerja($keyword);
        return $doc->responseGet($data);
    }

    #---coded by claude
    function getUnitKerjaConfig() {
        global $self, $doc;
        $data = $self->getUnitKerjaConfig();
        return $doc->responseGet($data);
    }

    #---coded by claude
    function changePortal($vars = []) {
        global $self, $doc;
        $unitId = (int)($vars['id'] ?? 0);
        $portal = $_GET['portal'] ?? '';
        $nama = $_GET['nama'] ?? '';
        if ($unitId && $nama) {
            $self->changePortal($unitId, $portal, $nama);
        }
        return $doc->responseGet(['status' => 'ok', 'unit_id' => $unitId, 'unit_nama' => $nama]);
    }

    #---coded by claude
    function resetPortal() {
        global $self, $doc;
        $self->resetPortal();
        return $doc->responseGet(['status' => 'ok']);
    }

    function getRolePrivilege() {
        global $self, $doc;
        $response = $self->getRolePrivilege($self->ses->val['id']);
        
        return $response;
    }
}