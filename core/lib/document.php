<?php namespace Gov2lib;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/********************************************************************
*	Date		: Sunday, November 22, 2009
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI
*	Version		: 2 -> 27-Mar-07, 23:17
 *
* # ---- ver 2.1, 02-Apr-07, gabung head dan body ke doc
* # ---- ver 2.2, 29-Apr-09, tambah mainmenu dropdown
* # ---- ver 2.3, 25-Agu-09, tambah pagetitle di navpath
* # ---- ver 2.4, 28-Sep-09, tambah buildsql
* # ---- ver 2.5, 22-Nov-09, tambah buildcolumn
* # ---- ver 2.6, 28-Jul-11, tambah parse_request
* # ---- ver 2.7, 21-Sep-11, perbaikan error_message
* # ---- ver 3.0, 15 April 2014, downgrade untuk publikasi kpu
* # ---- ver 4.0, 21 September 2017, menggunakan namespace untuk dipakai dengan standard PSR-4
* # ---- ver 4.1, 05 April 2021, [rijal@cybergl.co.id], Menambahkan support keycloak BKN @line 280-326. Ref task : https://projects.cybergl.co.id/issues/3813
* # ---- ver 4.2, 13-Agu-21, [rijal@cybergl.co.id], @line 165. Merubah behavior take('app'), semula take akan men-override vue yg
*                               ada di folder app origin jika ditemukan komponen yg sama, sekarang sudah tidak.
*/

class document extends customException {
	function __construct () {
        global $config;
		$this->body=array();
		$this->body['_SERVER']=$_SERVER;
//		$this->body['debug']=$_GET['debug'];
        $this->body['webroot']=$config->webroot;
        $this->body['protocol']=$config->protocol;
        $this->body['ssonode']=$config->platform->ssonode;
	}

    function takeAll ($_appDir) {
        global $doc;
		$_components=array();
        $_dir=__DIR__."/../../apps/$_appDir/model";
		if (file_exists($_dir)) {
	        $_files = array_slice(scandir($_dir), 2);
	        foreach($_files as $_key => $_val) {
                $this->take($_appDir,str_replace(".php","",$_val));
	        }
		}
    }

    function take ($_appDir,$_class="",$_fn="",$_param="", $_dsn="") {
        global $loader,$config;
        // echo $_dsn;
        if (!$_class) {$_class=$_appDir;}
        $_handler = "\App\\".$_appDir."\model\\".$_class;
        try {
            $this->component($_appDir);
            if (class_exists($_handler)) {
                if (!$_dsn) {$_dsn=$config->domain->attr['dsn'];}
                $this->$_class=new $_handler($_dsn);
                $loader->addPath($this->$_class->templateDir,$_appDir);
                if ($_fn && !$_param) {
                    if (method_exists($this->$_class,$_fn)) {
                        $this->$_class->$_fn();
                    } else {
                        throw new \Exception('FunctionNotExist: '.$_handler.'\\'.$_fn.'()');
                    }
                } elseif ($_fn && $_param) {
                    $this->$_class->$_fn($_param);
                }
            } else {
                throw new \Exception('Class/NameSpaceNotExist: '.$_handler);
            }
        } catch (\Exception $e) {
			$this->exceptionHandler($e->getMessage());
		}
    }

    public function content ($_content="",$side="") {
        global $doc,$self;
        $caller=explode("\\",get_called_class());
        if ($caller[3]) {
            $_template=$self->{$caller[3]}->templateDir;
        }
        if (!$_template) {
            $_template=$self->templateDir;
        }
        if (!$_content && file_exists($_template."/".$caller[3].".html")) {
            $_content=$caller[3].".html";
        } elseif (!$_content && file_exists($_template."/index.html")) {
            $_content="index.html";
        }
        switch ($side) {
            case "right":
                $_contentName="contentRight";
            break;
            case "left":
                $_contentName="contentLeft";
            break;
            default:
                $_contentName="content";
        }
		if (!isset($doc->$_contentName)) {$doc->$_contentName=array();}
		$doc->counter();
        if ($caller[1]!="document" && !strstr($caller[1],"Handler")) {$_content='@'.$caller[1].'/'.$_content;}
		$doc->{$_contentName}[$doc->counter]=$_content;
	}

	public function sidebar ($_sidebar) {
        global $doc;
        $caller=explode("\\",get_called_class());
		if (!isset($this->sidebar)) {$this->sidebar=array();}
		$doc->counter();
        if ($caller[1]!="document") {$_sidebar='@'.$caller[1].'/'.$_sidebar;}
		$doc->sidebar[$doc->counter]=$_sidebar;
	}

	public function sidebarRight ($_sidebar) {
        global $doc;
        $caller=explode("\\",get_called_class());
		if (!isset($this->sidebarRight)) {$this->sidebarRight=array();}
		$doc->counter();
        if ($caller[1]!="document") {$_sidebar='@'.$caller[1].'/'.$_sidebar;}
		$doc->sidebarRight[$doc->counter]=$_sidebar;
	}

	function body ($var,$val) {
		$this->body[$var]=$val;
	}

    public function counter () {
        $this->counter++;
    }

    function vars ($vars) {
        if (is_array($vars)) {
            foreach($vars as $key => $val) {
                $this->body[$key]=$val;
            }
        }
	}

    function component ($_appDir) {
        //------ masih perlu perbaikan untuk cara baca single controller dan multi controller
        global $doc, $pageID;
		$_components=array();
        $_dir=__DIR__."/../../apps/$_appDir";
        /*
        switch (STAGE) {
            case "prod":
            case "build":
            case "local":
            case "dev":
                $_dir.="/vue";
            break;

                $_dir.="/js";
            break;

        }
        */
        $_dir.="/vue";
		if (file_exists($_dir)) {
	        $_files = array_slice(scandir($_dir), 2);
            $column = $doc->body['components'][$pageID]; unset($column);
	        foreach($_files as $_key => $_val) {
                if (substr($_val,-4)==".vue" && substr($_val,0,1)!="_") {
                    // if(isset($column)){
                    if (!in_array($_val, array_column( $column ?? [], 'component'))) {
                        $_components[$_key]["component"]=$_val;
                        $_components[$_key]["tag"]=str_replace(".vue","",$_val);
                        $_components[$_key]["pageID"]=$_appDir;
                    }//}
                }
                /*
	            switch (STAGE) {
	                case "prod":
	                case "build":
            		case "local":
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
                */
	        }
			$doc->body['components'][$_appDir]=$_components;
        }
    }

	function error ($_code,$_message) {
		if (!isset($this->error)) {$this->error=array();}
		$this->error[$_code]=$_message;
	}

    function response ($_class,$_callback="",$_id=0) {
        global $_POST, $vars, $doc;
        if (is_array($doc->error)) {
            foreach($doc->error as $_key => $_val) {
                $_message.="$_key: $_val\n";
            }
        } else {
            if ($_POST["cmd"]) {$_cmd=strtoupper($_POST["cmd"]);}
            if ($vars["cmd"]) {$_cmd=strtoupper($vars["cmd"]);}
            $_message="Operasi $_cmd berhasil";
            if ($_id) {
                $_message.=" dengan nomor ID ".$_id;
            }
        }
        $_response["class"]=$_class;
        $_response["notification"]=$_message;
        $_response["callback"]=$_callback;
        $_response["id"]=$_id;
        if ($_POST["parent_id"]) {$_response["parent_id"]=$_POST["parent_id"];}
    return $_response;
    }

    function responseGet ($data) {
        global $doc;
        if (!$doc->error) {
            $response=$data;
        } else {
            $response=$doc->response("is-danger","openErr");
            header("HTTP/1.1 422 Query Fails");
        }
        return $response;
    }

    function responseAuth ($data=null) {
        global $doc,$self, $pageID;
        if (!$doc->error) {
            $response=$data;
        } else {
            $response=$doc->response("is-danger","openSnackbar");
            $response["server"]=$_SERVER['SERVER_NAME'];
            $response["app"]=$pageID;
            $response["endpoint"]=$self->className;
            header("HTTP/1.1 401 Unauthorized");
        }
        return $response;
    }

    function renderJS () {
        global $vueData,$vueCreated,$vueMethods,$vueWatch;
        if (is_array($vueData)) {
            $this->body("vueData",json_encode($vueData));
        }
        $this->body("vueCreated",$vueCreated);
        $this->body("vueWatch",$vueWatch);
        $this->body("vueMethods",$vueMethods);
        $this->body("externalJS",$this->externalJS);
    }

    function render () {
        global $twig,$template,$doc,$self,$loader,$config, $pageID;
        $this->body("sidebars",$this->sidebar);
        $this->body("sidebarsRight",$this->sidebarRight);
        if (is_array($this->error)) {
            $this->body("pageTitle",'Exception Occured');
            $this->body("subTitle","Please check exception list below ");
            $this->body("errors",$this->error);
            if ($this->error['NotLogin']) {
                $_errorBody=array('errorMessage.html');
            } else {
                $_errorBody=array('@components/gov2navBreadcrumb.html');
                array_push($_errorBody,'errorMessage.html');
            }
            array_push($_errorBody,'@components/gov2notification.html');
            if ($this->error['ErrToken']) {
                array_push($_errorBody,'tokenMan.html');
            }
            if ($this->error['NoSession']) {
                array_push($_errorBody,'tokenForm.html');
            }
            if ($this->error['NotLogin']) {
                /*------------------- Support keycloak ---------------------------------*/
                $keycloak = $config->keycloak;
                // false for auto redirect to SSO BKN. set to true to auto redirect to gov2sso
                $is_gov2 = true;
                if(isset($_GET['type']) && $_GET['type'] === 'gov2') {
                    $is_gov2 = true;
                }
                if ($keycloak && (int)$keycloak->active && !$is_gov2) {
                    $options = array(
                        'clientId' => (string)$config->domain->attr['clientId'],
                        'clientSecret' => (string)$config->domain->attr['clientSecret'],
                        'urlAuthorize' => str_replace("{realm}", (string)$config->domain->attr['realm'], (string)$keycloak->urlAuthorize),
                        'urlAccessToken' => str_replace("{realm}", (string)$config->domain->attr['realm'], (string)$keycloak->urlAccessToken),
                        'urlResourceOwnerDetails' => str_replace("{realm}", (string)$config->domain->attr['realm'], (string)$keycloak->urlResourceOwnerDetails)
                    );
                    $provider = new GenericProvider($options);
                    if (!isset($self->ses->val['account_id'])) {
                        try {
                            $authorizationUrl = $provider->getAuthorizationUrl();

                            // Get the state generated for you and store it to the session.
                            $_SESSION['oauth2state'] = $provider->getState();

                            $GLOBALS['vueData']['href'] = $authorizationUrl;
                            $GLOBALS['vueCreated'].='vm = this;
                                    eventBus.$emit("openNotif",
                                        {class: "info",
                                            callback:"infoSnackbar",
                                            notification: "Page akan di redirect ke login page KeyCloak dalam 2 detik"});
                                    setTimeout(function(){
                                        vm.$window.location.href = vm.href;
                                    }, 2000)
                                ';
                        } catch (IdentityProviderException $e) {
                            $this->exceptionHandler($e->getMessage());
                        }
                    } else {
                        if(date($self->ses->val['expired_in']) < date("Y-m-d H:i:s")){
                            $self->take("gov2login", "login");
                            try {
                                $self->login->createKeycloakSession($provider, 'refresh_token');
                            } catch (IdentityProviderException $e) {
                                $this->exceptionHandler($e->getMessage());
                            }
                        }
                    }
                } else {
                    //--------- DEFAULT phpVB NotLogin Exception -------------------------------------
                    $loader->addPath(__DIR__."/../../apps/gov2login/view","gov2login");
                    array_push($_errorBody,'@gov2login/notLogin.html');
                }
                /*------------------- END Support keycloak ---------------------------------*/
                // session_start();
                $_SESSION['ssonode']=trim($config->platform->ssonode);
                // print_r($_SESSION);
                // exit;
            }
            $this->body("contents",$_errorBody);
        } else {
            $this->body("contents",$this->content);
            $this->body("contentsRight",$this->contentRight);
            $this->body("contentsLeft",$this->contentLeft);
        }
        $this->renderJS();
        $template = $twig->load($doc->baseBody);
        echo $template->render($this->body);
    }

    function externalJS ($_codeSnippet) {
        global $doc;
        $caller=explode("\\",get_called_class());
		if (!isset($this->externalJS)) {$this->externalJS=array();}
		$doc->counter();
        if ($caller[1]!="document") {$_codeSnippet='@'.$caller[1].'/'.$_codeSnippet;}
		$doc->externalJS[$doc->counter]=$_codeSnippet;
	}
    /*
    function pageRoles ($appDir) {
        global $doc;
        static $_roles=array();
        $_roleFilePath=__DIR__."/../../$appDir/xml/pageroles.xml";
        if (!$_roleFile) {
            $_roleFilePath=__DIR__."/../config/menu.".STAGE.".xml";
        } elseif ($_menuFile && !file_exists($_menuFilePath)) {
            unset($_menuFilePath);
        }
        if ($_menuFilePath) {
            $_menu = simplexml_load_file($_menuFilePath, "SimpleXMLElement", LIBXML_NOCDATA);
            $_json=json_decode(json_encode($_menu),TRUE);
            if (!is_array($_json['menu'][0])) {
                $_singleMenu=$_json['menu'];
                unset($_json['menu']);
                $_json['menu'][sizeof($_menus)+1]=$_singleMenu;
            }
            array_push($_menus,$_json);
            //print_r($_menus);
            $doc->body['menus'] = $_menus;
            return $_menus;
        } else {
            throw new \Exception('NoNavXMLFile:'.$_menuFile);
        }
    }
    */

    function envRead ($data) {
        global $publickey;
        $secretKey = base64_encode(random_bytes(64));
        $data = isset($data) ? $data : $secretKey;
        $_result= JWT::decode($data, new Key($publickey, 'HS256'));
        //$_result=\Firebase\JWT\JWT::decode($data, $publickey, array('HS256'));
        return json_decode(json_encode($_result), true);
    }
}
?>