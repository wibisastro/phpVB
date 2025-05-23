<?php namespace App\gov2survey;


class api extends \Gov2lib\api 
{

    function __construct () {
        global $self;
        parent::__construct();
        // $this->authorize();

        $self->scrollInterval=100;
        // $self->ses->authenticate('member');
        
        // $self->ses->authenticate($self->opt->mvc()->active ? 'member' : 'closed');
    }

    function countKuesioner ($vars)
    {
        global $self;
        return $self->countKuesioner($vars['id']);
    }

    function getKuesioner ($vars) 
    {
        global $self;
        $response=$self->getKuesioner($vars['scroll'], $vars['id']);
        return $response;
    }
    
    function getKuesionerChild ($vars) 
    {
        global $self;
        $response=$self->getKuesionerChild();
        return $response;
    }
    
    function getSurvey ($vars) 
    {
        global $self;
        $response=$self->getSurvey();
        return $response;
    }

    function insert_survey ($vars) 
    {
        global $self, $doc;
        $data = $vars['data'];
        $self->insert_survey($data);
        return $doc->responseGet($data);
    }

    function insert_kuesioner ($vars)
    {
        global $self, $doc;
        $data = $vars['data'];
        $self->insert_kuesioner($data);
        if ($doc->error) {
            return ['error' => $doc->response('danger', 'infoSnackbar')];
        }
        return $doc->responseGet($data);
    }
}
