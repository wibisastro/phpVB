<?
/********************************************************************
*	Date		: 5 May 2015
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@cybergl.co.id
*	Copyright	: Cyber GovLabs 
*********************************************************************/
require("../conf/config.php"); 

#------------------------init
$doc->pagetitle="Admin";

switch($_GET["cmd"]) {
    default:
        $doc->content("general/index.php");
}
$doc->error_message();

#------------------------view
include(viwpath."/general/body.php");
?>