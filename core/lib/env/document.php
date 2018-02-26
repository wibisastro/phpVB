<?php namespace Gov2lib\env;
/********************************************************************
*	Date		: Sunday, November 22, 2009
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*	Version		: 2 -> 27-Mar-07, 23:17 

# ---- ver 2.1, 02-Apr-07, gabung head dan body ke doc
# ---- ver 2.2, 29-Apr-09, tambah mainmenu dropdown
# ---- ver 2.3, 25-Agu-09, tambah pagetitle di navpath
# ---- ver 2.4, 28-Sep-09, tambah buildsql
# ---- ver 2.5, 22-Nov-09, tambah buildcolumn
# ---- ver 2.6, 28-Jul-11, tambah parse_request
# ---- ver 2.7, 21-Sep-11, perbaikan error_message
# ---- ver 3.0, 15 April 2014, downgrade untuk publikasi kpu
# ---- ver 4.0, 21 September 2017, menggunakan namespace untuk dipakai dengan standard PSR-4
*/

class document extends customException {
	function __construct () {
        global $config;
		$this->body=array();
		$this->body['_SESSION']=$_SESSION;
		$this->body['_SERVER']=$_SERVER;
		$this->body['debug']=$_GET['debug'];
        $this->body['webroot']=$config->url->{STAGE.'root'};
	}

    function model ($_appDir,$_fn="",$_param="") {
        $_handler = "\App\\".$_appDir."\model\index";
        try {
            if (class_exists($_handler)) {
                $GLOBALS[$_appDir]=new $_handler;
                if ($_fn && !$_param) {
                    $GLOBALS[$_appDir]->$_fn();
                } elseif ($_fn && $_param) {
                    $GLOBALS[$_appDir]->$_fn($_param);
                }
            } else {
                throw new \Exception('ClassNotExist:'.$_handler);
            }   
        } catch (\Exception $e) {
			$this->exceptionHandler($e->getMessage());
		}
    }
    
	function content ($_content) {
		if (!isset($this->content)) {$this->content=array();}
		$_index=sizeof($this->content);
		$_index++;
		$this->content[$_index]=$_content;
	}
	
	function body ($var,$val) {
		$this->body[$var]=$val;
	} 

    function component ($_appDir) {
		static $_components=array();
        $_dir=__DIR__."/../../app/$_appDir";
        switch (STAGE) {
            case "build":
            case "dev":
                $_dir.="/vue";
            break;
            case "prod":
                $_dir.="/js";
            break;
        }
		if (file_exists($_dir)) {
	        $_files = array_slice(scandir($_dir), 2);
	        while (list($_key,$_val)=each($_files)) {
	            switch (STAGE) {
	                case "build":
	                case "dev":
	                    if (substr($_val,-4)==".vue" && substr($_val,0,1)!="_") {
	                        $_components[$_key]["component"]=$_val;
	                        $_components[$_key]["tag"]=str_replace(".vue","",$_val);
                            $_components[$_key]["pageID"]=$_appDir;
	                    }
	                break;
	                case "prod":
	                    if (substr($_val,-3)==".js") {
	                        $_components[$_key]["component"]=$_val;
	                        $_components[$_key]["tag"]=str_replace(".js","",$_val);
                            $_components[$_key]["pageID"]=$_appDir;
	                    }
	                break;
	            }
	        }
			$this->body['components']=$_components;
		}
    }
    
	function error ($_error) {
		if (!isset($this->error)) {$this->error=array();}
		$index=sizeof($this->error);
		$index++;
		$this->error[$index]=$_error;
	}
    
    function render () {
        global $template;
        if (is_array($this->error)) {
            $this->body("pageTitle",'Exception Occured');
            $this->body("subTitle","Please check exception list below ");
            $this->body("errors",$this->error);
            $_errorBody=array('errorMessage.html');
            $this->body("contents",$_errorBody);
        } 
        echo $template->render($this->body);
    }
}
?>