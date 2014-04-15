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
$doc->status="GET Method";
$doc->leftside("get/submenu.php");

#------------------------proc
switch ($_GET["cmd"]) {
	case "install":
		$doc->content("get/install.php");
	break;
	case "todo":
		$doc->content("get/todo.php");
	break;
	case "manifest":
		$doc->content("get/manifest.php");
	break;
	case "changelog":
		$doc->content("get/changelog.php");
	break;
	default:
		$doc->content("get/index.php");

}

$doc->error_message();

#------------------------view

if ($mobile) {include(viwpath."/general/m_body.php");}
else {include(viwpath."/general/body.php");}
?>