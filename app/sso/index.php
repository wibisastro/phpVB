<?php
$main=new SSO\model\sso;
try {
	switch($vars["cmd"]) {
	    case "fbconnect":
			$doc->body("pageTitle",'Gov 2.0 Facebook Connect');
			$doc->content("ssoFbconnect.html");
			$doc->body("contents",$doc->content);
	    break;
	    case "activation":
			$doc->body("pageTitle",'Gov 2.0 Activation');
			$doc->content("ssoActivation.html");
			$doc->body("contents",$doc->content);
	    break;
	    case "signup":
			$doc->body("pageTitle",'Gov 2.0 Registration');
			$doc->content("ssoSignup.html");
			$doc->body("contents",$doc->content);
	    break;
	    default:
			$doc->body("title",'SSO');
	        if ($gov2->error) {$doc->body("pageTitle",'Gov 2.0 SSO Login');}
	        else {$doc->body("pageTitle",'Gov 2.0 SSO Profile');}
	}
	$template = $twig->load($sso->baseName.'Body.html');
} catch (Exception $e) {
	echo $e->getMessage();
}

echo $template->render($doc->body);