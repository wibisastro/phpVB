<?
/********************************************************************
*	Date		: Thursday, August 25, 2011
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*********************************************************************/




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

	

#---------------------------------------initialization
#-----do not change this
	

?>