<?
/********************************************************************
*	Date		: 15 April 2014
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*********************************************************************/
require("config.php"); 
#------------------------init
//$ses->authenticate();
$doc->pagetitle="Example";
$doc->status="Post Method";

#------------------------proc
switch ($_POST["cmd"]) {
	case "Submit":
		$doc->content("post/success.php");
	break;
	default:
		$doc->content("post/form.php");

}

$doc->error_message();

#------------------------view

if ($mobile) {include(viwpath."/general/m_body.php");}
else {include(viwpath."/general/body.php");}
?>