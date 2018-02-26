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

$gov2=new gov2model;
$gov2->authorize("guest");

#------------------------init
$doc->content("gov2view.php");

switch($_GET["cmd"]) {
    case "fbconnect":
        $doc->pagetitle="Gov 2.0 Facebook Connect";
        $view="fbconnect";
    break;
    case "activation":
        $doc->pagetitle="Gov 2.0 Activation";
        $view="activation";
    break;
    case "signup":
        $doc->pagetitle="Gov 2.0 Registration";
        $view="signup";
    break;
    default:
        if ($gov2->error) {$doc->pagetitle="Gov 2.0 Login";}
        else {$doc->pagetitle="Gov 2.0 Profile";}
}

$doc->error_message();

#------------------------display
include(VIWPATH."/general/body.php");
?>