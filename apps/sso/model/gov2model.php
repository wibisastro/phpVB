<?php namespace SSO\model;
/********************************************************************
*	Date		: 25 Mar 2015
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: e-Gov Lab Univ of Indonesia 
*********************************************************************/

session_start();

class gov2model {
	function gov2model () {
        global $cases;
	    $this->timeout_session	= 60*5; #-----5 menit
		$this->timeout_cookies	= 60*60; #----1 jam        
        if (isset($_GET['info'])) {
            switch($_GET['info']) {
                case "cases":echo json_encode($cases);break;
                case "cookie":echo json_encode($_COOKIE);break;
                case "session":echo json_encode($_SESSION);break;
            }
            exit;
        }
	}

    function readxml ($filename) {
        if (file_exists(GOV2XMLPATH."/".$filename.".xml")) {return simplexml_load_file(GOV2XMLPATH."/".$filename.".xml");}
        else {return "NotExist";}    
    } 
    
    function authorize ($privilege="member") {
        global $public,$secret,$_GET,$_POST;
        $valid="";
        if (!isset($_SESSION['account_id']) && $privilege!="public") {$error="NotLogin";}
        else {
  //          if (time()-$gov2cookies["started"] > time()+$this->timeout_session) {$error="SessionExpired";}
   //         else {
                if ($privilege=="public") {unset($error);} else {
                    if ($privilege=="member" || $privilege=="webmaster") {
                        if ($_SERVER["SERVER_NAME"]=="localhost") {$members=$this->readxml("gov2member_local");}
                        else {$members=$this->readxml("gov2member");}
                        if ($members && $members!="NotExist") {
                            foreach ($members->member as $member) {
                                if ($member->account_id==$_SESSION["account_id"]) {
                                    $valid=$member;
                                    unset($error);
                                    break;
                                } else {$error="NotMember";}
                            }
                            if (!$error) {
                                if (!$valid->webmaster) {
                                    foreach ($valid->privilege as $cases) {
                                        $controller = $cases->attributes();
                                        if ($controller['controller']==$_SERVER['SCRIPT_NAME']) {
                                            unset($error);
                                            if (!$_GET['cmd'] && !$_POST['cmd'] && !is_array($cases->case)) {break;} 
                                            else {
                                                foreach ($cases->case as $case) {
                                                    if ($_GET['cmd']==$case || $_POST['cmd']==$case) {unset($error);break;} 
                                                    else {$error="UnauthorizedCase";}              
                                                }
                                                if (!$error) {break;}
                                            }
                                        } else {$error="UnauthorizedPage";}
                                    }
                                }

                            }                 
                        } else {$error="NotConfigured";}
                    }
                    $_SESSION["started"]=time()+$this->timeout_session;
                    $_SESSION["counter"]++;
//                    $this->cookie_save('started,counter');
                } 
 //           }
        } 
        $this->authorized=$_SESSION;
        $this->error=$error;
    }
}
?>