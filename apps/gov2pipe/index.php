<?php namespace App\gov2pipe;

class index extends \Gov2lib\api {
    function __construct () {
        global $self,$doc;
        parent::__construct();
        $_className=$self->className;
		$self->takeAll("components");
        $self->scrollInterval=100;
        $doc->body("className",$_className);
        $self->ses->authenticate('public');
    }

    function index () {
        global $self,$doc, $pageID;
        $self->gov2nav->setCustomNav();
        $self->loadTable();
        $doc->body("pageTitle",'Gov2pipe');

        $options = $self->opt->get(['app' => $pageID, 'nama' => 'ssonode']);
        $doc->body('ssonode', $options['value']);
        $self->content();
    }

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
        global $self;
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
        global $self, $pageID;
        $response = $self->gov2nav->menubar($pageID, 'menu.xml');
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
