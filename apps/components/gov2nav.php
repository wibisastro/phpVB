<?php namespace App\components;
    
class gov2nav {
    function __construct () {
		
    }
    
    function index () {
        global $doc,$self;
        $doc->body("pageTitle",'Navigation Component');
        $self->ses->authenticate('public');
		$self->take("components","gov2nav", "setDefaultNav");
    }
    
    function breadcrumb ($vars) {
        global $self,$config,$doc;
        if ($vars['xml']) {$xml=$vars['xml'].".xml";}
        $cmdID = $_GET['cmdID'] ?? '';
        $self->menus=$self->menubar($vars['pageID'],$xml);
        // Reset eksplisit — walker REPLACE $self->breadcrumb saat match.
        $self->breadcrumb = [];
        // Coba match level-3 dulu kalau cmdID di-set; fallback ke level-2 bila tidak ketemu.
        if ($cmdID) {
            $self->breadcrumb($self->menus,$vars['pageID'],$vars['className'],$cmdID);
        }
        if (empty($self->breadcrumb)) {
            $self->breadcrumb($self->menus,$vars['pageID'],$vars['className']);
        }
        // Fallback untuk app index (mis. /aisakip, /ingest) tanpa URL match di menu —
        // pakai top-level menu item caption sebagai breadcrumb.
        if (empty($self->breadcrumb)) {
            foreach ($self->menus as $_wrapper) {
                if (isset($_wrapper['menu']) && is_array($_wrapper['menu'])) {
                    foreach ($_wrapper['menu'] as $_top) {
                        if (is_array($_top) && !empty($_top['caption'])) {
                            $self->breadcrumb[] = [
                                "caption" => $_top['caption'],
                                "url" => $config->webroot.($_top['url'] ?? ''),
                            ];
                            break 2;
                        }
                    }
                }
            }
        }
        if (!$doc->error) {
            $c=1;
            $url=json_decode(json_encode($config->webroot),true);
            $data[0]=array("caption"=>"Home","url"=>$url[0] ?? "/");
            foreach($self->breadcrumb as $val) {
                if (!empty($val['caption'])) {
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