<?
/********************************************************************
*	Date		: 15 April 2014
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*********************************************************************/
require("../conf/config.php"); 
#------------------------init
$doc->pagetitle="Example";

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
include(viwpath."/general/body.php");
?>