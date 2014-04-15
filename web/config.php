<?
/********************************************************************
*	Date		: Thursday, August 25, 2011
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*********************************************************************/

#---------------------------------------admin configuration
#-----change this to your local installation folder
	define("site","http://localhost");	
	define("dirpath",$_SERVER["DOCUMENT_ROOT"]."/framew0.0.2");
    
#---------------------------------------path configuration
#-----mostly do not need to change this

	define("cnfpath",dirpath."/conf");
	define("modpath",dirpath."/model");
	define("viwpath",dirpath."/view");
	define("ctrpath",dirpath."/web");
	define("imgpath",ctrpath."/images");

#---------------------------------------url configuration
#-----change this only if you know what you're doing
	define("siturl",$_SERVER["HOST"]);
	define("imgurl",siturl."web/images");

#---------------------------------------database configuration
#-----do not change this
	require(cnfpath."/config_db.php");

#---------------------------------------module recruiter
#-----do not change this

function getmodule ($name) {
	if (is_array($name)) {
		while (list($key,$val)=each($name)) {if (file_exists(modpath."/$val.php")) require modpath."/$val.php";}
	} else {
		if (file_exists(modpath."/$name.php")) {require modpath."/$name.php";$result=new $name;} 
		else {echo "Module $name not exist...";}
	}
return $result;
}

#---------------------------------------environtment configuration
#-----replace this with your email
	define("webmaster","wibi@alumni.ui.ac.id");
	define("remoteip",getenv("REMOTE_ADDR"));
	define("servername",getenv("SERVER_NAME"));
	define("cookie_name","gov2labegov");
	define("member_cookie",$_COOKIE["member_".cookie_name]);	
	define("cookie",$_COOKIE[cookie_name]);
	$querystring	= getenv("QUERY_STRING");
	$PHP_SELF=$_SERVER["PHP_SELF"];
	if (strstr($HTTP_USER_AGENT,"Mobile") || strstr($HTTP_USER_AGENT,"Symbian") ) {$mobile=true;}
	else {$mobile=false;}

#---------------------------------------color configuration
#-----change this with your color (this is use for the zebra table)

	$color1="#f6f3d7";
	$color2="#fbe0bb";
	$color3="#fdd6a1";

#---------------------------------------initialization
#-----do not change this
	$api	= getmodule("api");
	$ses	= getmodule("session");
	$doc	= getmodule("document");
	$frm	= getmodule("formage");

	$secret="";
	$public="";

	define("css_url","/css");
	define("js_url","/js");


?>