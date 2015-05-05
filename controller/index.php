<?
/********************************************************************
*	Date		: 15 April 2014
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI. 
*********************************************************************/
require("config.php"); 
#------------------------init
$doc->pagetitle="Lab E-Gov";
$doc->status="the working platform";
$doc->content("general/headerbody.php");

$doc->error_message();

#------------------------view
if ($mobile==true) {include(viwpath."/general/m_body.php");}
else {include(viwpath."/general/body.php");}
?>