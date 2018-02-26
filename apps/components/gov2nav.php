<?php namespace App\components;
    
class gov2nav {
    function __construct () {
		
    }
    
    function index () {
        global $doc,$self;
        $doc->body("pageTitle",'Navigation Component');
		$self->setDefaultNav();
    }
    
    function breadcrumb ($vars) {
        global $self,$config,$doc;
        if ($vars['xml']) {$xml=$vars['xml'].".xml";}
//        if ($vars['className']=="index") {unset($xml);}
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
}