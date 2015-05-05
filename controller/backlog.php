<?
/********************************************************************
*	Date		: 11 Nov 2014
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@cybergl.co.id
*	Copyright	: PT Cyber GovLab 
*********************************************************************/
require("../conf/config.php"); 


#------------------------init


#------------------------controller
$doc->pagetitle="Product Backlog";

switch ($_GET['cmd']) {
        default:
            $url_service="http://standar.gov2.web.id/backlog.php?cmd=handy&url=".$_SERVER["SERVER_NAME"]."/backlog.xml";
            $doc->content("general/webclient.php");
}

$doc->error_message();

#------------------------view
include(viwpath."/general/body.php");
?>