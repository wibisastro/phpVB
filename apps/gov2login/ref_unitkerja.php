<?php namespace App\gov2login;
    
class ref_unitkerja {
    function __construct () {  
		global $self;
        $self->scrollInterval=100;
        $self->ses->authenticate('member');
    }

    function breadcrumb ()
    {
        global $self;
        return $self->getBreadcrumb('Unit Kerja');
    }
    
    function count ($vars)
    {
        global $self,$doc;
        $data=$self->getCount($vars['id']);
        return $doc->responseGet($data);   
    }
    
    function fields ()
    {
        global $self;
        return $self->fields;
    }

     function table ($vars)
     {
        global $doc,$self;
        $data=$self->getRecords($vars);
        if (sizeof($data)==0) {$data=array("data"=>"empty","level"=>"1");}
        return $doc->responseGet($data);   
    }
}