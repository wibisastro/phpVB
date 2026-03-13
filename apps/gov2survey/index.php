<?php namespace App\survey;

class index extends \Gov2lib\api {

    function __construct() {
        global $self;
        parent::__construct();
		$self->takeAll("components");
        if (isset($self->ses->val['account_id'])) {
            $self->ses->authenticate('member');
        }
        else {
            $self->ses->authenticate('public');
        }
        $self->scrollInterval=100;
    }

    function index() {
        global $self,$doc;
        $self->gov2nav->setDefaultNavCustom();
        $doc->body("pageTitle",'Survey');
        $doc->body("subTitle",'Kuesioner');
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
