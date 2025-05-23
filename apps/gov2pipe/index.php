<?php namespace App\gov2pipe;

class index extends \Gov2lib\api {
    function __construct () {
        global $self,$doc;
        parent::__construct();
        $_className=$self->className;
		$self->takeAll("components");
        $self->scrollInterval=100;
//        $self->take("renjakl","widget");
        $doc->body("className",$_className);
        $self->ses->authenticate('public');
        $this->DEBUG_MODE = strpos($_SERVER['SERVER_NAME'], 'bkn.go.id') !== false;
    }

    function index () {
        global $self,$doc, $pageID;
        $self->gov2nav->setDefaultNavCustom();
        $self->loadTable();
        $doc->body("pageTitle",'Gov2pipe');

        $options = $self->opt->get(['app' => $pageID, 'nama' => 'ssonode']);
        $doc->body('ssonode', $options['value']);
        $self->content();
    }
    /*
    function pipedin () {
        global $self,$doc;
        $self->gov2nav->setDefaultNavCustom();
        $self->loadTable();
//        $doc->body('pipedin', $_SESSION);
        $doc->body("pageTitle",'Piped-In');
        $self->content('pipedin.html');
    }
*/
    function sessionform () {
        global $self;
        $self->fields = $self->gov2formfield->getTokenForm('session');
        return $self->fields;
    }

    function tokenform () {
        global $self;
        $self->fields = $self->gov2formfield->getTokenForm('token');
        return $self->fields;
    }

    function session ($vars) {
        $response=$this->createSession($vars);
        return $response;
    }

    function sessionService($vars) {
        global $config;
        $_service_url = join('/', [$vars['serviceAddr'], $vars['appAddr']]);
        $_data = array(
            'cmd' => 'session',
            'data' => array('token' => $vars['token'], 'apikey'=> (STRING) $config->apikey->public)
        );
        $response = json_decode($this->putdata($_service_url, $_data), 1);

        if ($response['class'] === 'is-success') {
            $_SESSION['tokenBearer'] = $vars['token'];
        }

        return $response;
    }


    function breadcrumb ($vars) {
        global $self,$config;
        $response=$self->widget->getBreadcrumb("Kementerian");
        if ($response[sizeof($response)-1]['level']<2) {
            $_id=62;
            $response=$self->widget->getBreadcrumb("Kementerian",$_id);
        }
        return $response;
    }


    function getHeaders () {
        $response = simplexml_load_file(__DIR__."/xml/header.xml");
        return json_encode($response);
    }

    function getMenus () {
        global $self, $pageID,$config;

        // if ($this->DEBUG_MODE) {
        //     $_sub_domain    = 'siap.bkn.go.id';
        // } else {
        //     $_sub_domain    = 'bkn.kl2.web.id';
        // }
        // $domain = join(".", ['renobiro', $_sub_domain]);
        
        $dsn_id = (int)$self->dsn_id;
        $dsn    = (string)$self->dsn;
        $klBkn=$self->getKlBkn($dsn_id);

        if($dsn == 'renobiro.bkn.kl2.web.id' || $dsn == 'reno.bkn.go.id'){
            $response=$self->gov2nav->menubar('telaahrenjakl', 'menu_biroren.xml');
        }else if($dsn == 'renobiro'){
            $response=$self->gov2nav->menubar($pageID, 'menu_biroren.xml');
        }else if($dsn == 'inspektorat'){
            $response=$self->gov2nav->menubar($pageID, 'menu_eselon2.xml');
        }else if($dsn == 'inspektorat.bkn.kl2.web.id' || $dsn == 'inspektorat.bkn.go.id'){
            $self->gov2nav->menubar('telaahrenjakl', 'menu_inspektorat.xml');
            $response=$self->gov2nav->menubar($pageID, 'menu_inspektorat.xml');
        }else if($klBkn['level_label'] == 'unit'){
            $response=$self->gov2nav->menubar($pageID, 'menu_eselon1.xml');
        }else if($klBkn['level_label'] == 'direktorat'){
            $response=$self->gov2nav->menubar($pageID, 'menu_eselon2.xml');
        }else{
            $response=$self->gov2nav->menubar($pageID, 'menu.xml');
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


    

    function phasebar(){
        global $self, $scriptID;
    }

    function paguTotal()
    {
        global $self, $doc;
        $unit_id = $self->ses->val['portal_id'];
        $data = array(
            'paguControl' => $self->getPaguControl($unit_id),
            'paguTotal' => 0
        );
        $data['paguTotal'] = $self->getPaguTotal($unit_id);
        if (is_array($doc->error )) {
            $data = $doc->response('danger', 'infoSnackbar');
        }
        return $doc->responseGet($data);
    }

    function paguControl()
    {
        global $self, $doc;
        $unit_id = $self->ses->val['portal_id'];
        $data = $self->getPaguControl($unit_id);
        if (is_array($doc->error )) {
            $data = $doc->response('danger', 'infoSnackbar');
        }
        return $doc->responseGet($data);
    }

    function process($vars)
    {
        global $self, $doc;
        $ct_endpoint_option = $self->opt->get(['nama' => 'process']);
        $ct_domain = $ct_endpoint_option['value'];
        $ct_app = "renjakl";
        $ct_mvc = "index";
        $ct_fn = "reno_service_renjakl_count";
        $ct_fn_telaah = "reno_service_telaah";
        $ct_fn_refresh = "reno_service_refresh";
        $ct_endpoint = join('/', [$ct_domain, $ct_app, $ct_mvc, $ct_fn]);
        $ct_endpoint_telaah = join('/', [$ct_domain, $ct_app, $ct_mvc, $ct_fn_telaah]);
        $ct_endpoint_refresh = join('/', [$ct_domain, $ct_app, $ct_mvc, $ct_fn_refresh]);
        $ct_service = $this->getdata($ct_endpoint);
        $ct_telaah = $this->getdata($ct_endpoint_telaah);
        $ct_refresh = $this->getdata($ct_endpoint_refresh);

        $status = array(
            'crud' => $self->status_crud($ct_service),
            'submit' => $self->status_submit($ct_service),
            'reset' => $self->status_reset($ct_service),
            'telaah' => $ct_telaah['telaah'],
            'publish' => $self->status_publish($ct_service),
            'refresh' => $ct_refresh['refresh']
        );

        if (is_array($doc->error)) {
            return $doc->response('danger');
        }
        return $status;
    }

    /**
     * fn ini harus di execute di portal reno.
     * @return array
     */
    function reno_service_renjakl_count()
    {
        global $self, $doc;
        $data = $self->reno_service_renjakl_count();
        return $doc->responseGet($data);
    }

    /**
     * fn ini harus di execute di portal reno.
     * @return array
     */
    function reno_service_telaah()
    {
        global $self, $doc;
        $data = $self->status_telaah();
        return $doc->responseGet($data);
    }

    /**
     * fn ini harus di execute di portal reno.
     * @return array
     */
    function reno_service_refresh()
    {
        global $self, $doc;
        $data = $self->status_refresh();
        return $doc->responseGet($data);
    }

}