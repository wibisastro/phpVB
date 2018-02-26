<?php namespace App\components;
    
class gov2formfield {
	function __construct ($method="") {
		global $self;
		$this->fields = $self->getFields(__DIR__."/json/formfield.json");
    }
    
    function index () {
        global $doc,$self;
		$self->take("components","gov2nav", "setDefaultNav");
		$self->take("components","gov2button");
		$self->take("components","gov2notification");
        $self->demo();
        $doc->body("pageTitle",'Formfield Component');
        $self->gov2button->content();
        $self->gov2notification->content();
        $self->content();

    }
    
    function add ($vars="") {
        global $self,$config,$doc;
        $errors=$self->checkRequired($_POST,$this->fields);
        if (is_array($errors)) {
            $response=$errors;
            $response["class"]="is-warning";
            $response["notification"]="Harap isi form dengan lengkap";
            header("HTTP/1.1 422 Incomplete fields");                    
        } else {
            $response=$doc->response("is-primary","toggleForm","123456789");
        }
		return $response;
    }
	
	function fields ($vars="") {
        global $doc;
        return $this->fields;
    }
}