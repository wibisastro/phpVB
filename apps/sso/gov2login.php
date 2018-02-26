<?
/********************************************************************
*	Date		: 25 Mar 2015
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: e-Gov Lab Univ of Indonesia 
*********************************************************************/

require("gov2model.php");
$cases=array("sessave","authorized","login","logout","authorize","default");

$gov2=new gov2model;

#------------------------controller
$gov2->authorize();

$member=array();

if ($_POST) {
      $data=json_decode(stripslashes($_POST["req"]));
      while(list($key,$val)=each($data)) {${"$key"}=$val;}
}

switch ($cmd) {
    case "sessave":
        if ($token) {$view="escape";} 
        else {$gov2->error="NoID";}
    break;
    default:
        switch ($_GET["cmd"]) {
            case "login":
                header("Location: ".account_url."/slogin.php?cmd=request&client=".$_SERVER["SERVER_NAME"]);
            break;
            case "logout":
                session_destroy();
                header("Location: ".account_url."/slogout.php?client=".$_SERVER["SERVER_NAME"]);
                exit;
            break;
            case "authorize":
                if ($_GET['token']) {
                    $authorized=file_get_contents(account_url."/slogin.php?cmd=authorize&token=".$_GET['token']);
                    $data=json_decode($authorized,1);
                    if (!$data['error']) {
                        $landingpage=$_SESSION["landingpage"];
                        $servicepage=$_SESSION["servicepage"];
                        session_destroy();
                        session_start();
                        $_SESSION["account_id"]=$data['account_id'];
                        $_SESSION["fullname"]=$data['fullname'];
                        $_SESSION["facebook"]=$data['facebook'];
                        $_SESSION["email"]=$data['email'];
                        $_SESSION["photourl"]=$data['photourl'];
                        if ($servicepage) {$_SESSION["servicepage"]=$servicepage;}
                        if (!$landingpage) {$landingpage="index.php";}
                        header("Location: $landingpage");
                        exit;
                    } else {$gov2->error=$data['error'];}
                } else {$gov2->error="NoID";}
            break;
            default:
                if (!$gov2->error) {
                    if ($config->secure) {header("Location: https://".$_SERVER["SERVER_NAME"]);}
                    else {header("Location: http://".$_SERVER["SERVER_NAME"]);}
                }
        }
}

include("gov2view.php");
?>