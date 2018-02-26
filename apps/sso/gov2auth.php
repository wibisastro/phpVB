<?
/********************************************************************
*	Date		: 25 Mar 2015
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: e-Gov Lab Univ of Indonesia 
*********************************************************************/
$host=explode(".",$_SERVER["HTTP_HOST"]);
require("../../".$host[0]."/conf/config.php");
require("gov2model.php");

#------------------------init
$cases=array("guest","escape","identify");
$gov2=new gov2model;
$gov2->authorize($cases[0]);

#------------------------init
$doc->pagetitle="Gov 2.0 Service Authentication";

if (!$gov2->error) {
    if ($_POST) {
        switch($_POST["cmd"]) {
            
            default:
        }
    } else {
        switch($_GET["cmd"]) {
            case "escape":
                unset($_SESSION['active_client']);
                unset($_SESSION['subscriber_id']);
                unset($_SESSION['apikey']);
                unset($_SESSION['webroot']);
                if ($_SERVER['HTTP_REFERER']) {header("location: ".$_SERVER['HTTP_REFERER']);}
                else {header("location: index.php");}
                exit;
            break;
            case "identify":
                if ($_GET['apikey'] || $_SESSION['apikey']) {
                    if ($_GET['apikey']) {$apikey=$_GET['apikey'];}
                    else {$apikey=$_SESSION['apikey'];}
                    $valid=$api->subscriber_identify_bykey($apikey);
                } elseif ($_SESSION['client']) {
                    $valid=$api->subscriber_identify($_SESSION['client']);
                } else {
                    $valid=$api->subscriber_identify($_GET['client']);
                }
                if ($valid->subscriber_id) {
                    $_SESSION['active_client']=$valid->domain;
                    $_SESSION['subscriber_id']=$valid->subscriber_id;
                    $_SESSION['apikey'] = $valid->apikey_client;
                    $_SESSION['webroot'] = $_GET['webroot'];
                    if (isset($_SESSION['servicepage']) && $_SESSION['servicepage']!="/gov2auth.php") {
                        header("location: ".$_SESSION['servicepage']);
                        exit;
                    } else {
                        header("location: index.php");
                        exit;
                    }
                } else {
                    unset($_SESSION['active_client']);
                    unset($_SESSION['subscriber_id']);
                    unset($_SESSION['apikey']);
                    unset($_SESSION['webroot']);
                    $doc->error="UnregisteredClient";
                }
            break;
            default:
                if ($_SESSION["active_client"]) {
                    if (isset($_SESSION['servicepage'])) {header("location: ".$_SESSION['servicepage']);}
                    else {header("location: index.php");} 
                } else {
                    $doc->error="UnregisteredClient";
                }
        }
    }
} elseif ($_GET["cmd"]=="identify") {
    $_SESSION['servicepage']=$_SERVER['SCRIPT_NAME'];
    $_SESSION['landingpage']="gov2auth.php?".$_SERVER["QUERY_STRING"];
    header("location: ".account_url."/slogin.php?servicepage=1&client=".$_SERVER["SERVER_NAME"]);
    exit;
} else {
    $doc->error="UnregisteredClient";
}

$doc->error_message();

#------------------------view
include(viwpath."/general/body.php");
?>