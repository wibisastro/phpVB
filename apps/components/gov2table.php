<?php namespace App\components;
    
class gov2table {
    function __construct () {
		global $self;
		$self->scrollInterval=4;
		$self->takeAll("components");
    }
    
    function index () {
        global $doc,$self;
        $self->take("components","gov2nav", "setDefaultNav");
        $doc->body("pageTitle",'Table Component');
        $doc->body("subTitle",'Contoh Data Tabel Tematik');
        $self->gov2notification->content();
        $self->gov2search->content();
        $self->demo($this->scrollInterval);
        $self->content();
        $self->gov2pagination->content();
    }
    
    function count ($vars) {
        global $self,$doc;
        $data=$self->count();
		$response=$doc->responseAjax($data);
        return $response; 
    }
	
    function table ($vars) {
        global $self,$doc;
        $data=$self->browse($vars['scroll']);
		$response=$doc->responseAjax($data);
        return $response; 
    }
}