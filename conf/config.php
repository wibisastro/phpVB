<?
/********************************************************************
*	Date		: Thursday, August 25, 2011
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*********************************************************************/
#-----instalation helper, must be shut off upon success

switch ($_GET["error"]) {
    case "all":error_reporting(E_ALL);break;
    case "warning":error_reporting(E_ALL & ~E_NOTICE);break;
    case "notice":error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);break;
    default:
}
  ini_set("display_errors", 1);

#---------------------------------------path configuration, you can move this to improve security
    define("dirpath",str_replace("/controller","",$_SERVER["DOCUMENT_ROOT"]));
    define("conpath",dirpath."/controller"); #----- controller path
    define("modpath",dirpath."/model"); #----- model path
    define("viwpath",dirpath."/view"); #----- view path
    define("xmlpath",dirpath."/xml"); #----- xml doc path

#---------------------------------------module recruiter
#-----do not change this

function getmodule ($name) {
    if (file_exists(modpath."/$name.php")) {require modpath."/$name.php";$result=new $name;} 
    else {echo "Module $name is not exist...";}
return $result;
}

#---------------------------------------initialization
#-----do not change this
	$doc	= getmodule("document");
    $config = $doc->readxml('config');
?>