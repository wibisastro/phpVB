<?php namespace App\home;
    
class index {
    function __construct () {	 
    }
    
    function index () {
        global $self,$doc;
        $self->take("components","gov2nav", "setDefaultNav");
        $doc->body("pageTitle",'Home Page');
        $self->content();        
    }
}
/*
$doc->body("pageTitle",'Government 2.0 StarterKit');
switch ($_SERVER['SERVER_NAME']) {

    case "localhost":
        $doc->take("gov2nav","menubar","menu.apikrisna.xml");
    break;

    case "api.krisna.systems":
		header("location: apikrisna");
	break;
    case "api.kl2.web.id":
        $overview="Situs development prototype API service Krisna";
        $doc->take("gov2nav","menubar","menu.apikrisna.xml");
//        readfile("https://api.krisna.systems/apikrisna/table");
    break;
    case "dak.bappeda.web.id":

 $url="http://api.kl2.web.id/apikrisna/table/1/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1MTQwNDAzMTksImlzcyI6ImFwaS5rbDIud2ViLmlkIiwiYXVkIjoiaHR0cDpcL1wvZGFrLmJhcHBlZGEud2ViLmlkIiwiZXhwIjoxNTE0MDQzOTE5LCJrZXkiOiJmYWUyZWI4MDViYzc2Nzg2NmJmNmY3Y2Y2ZTU3ZjlkMiJ9.qO1cvpuYG-azqkO9ZyvkO_qTEmWl5igx2sjeW6ROMI4";
		$response=$api->getdata($url);
        if (!$doc->error) {echo $response;}
        else {
//            print_r($doc->error);

            if ($doc->error["ResponseError"]) {
                list($head,$body)=explode("application/json",$doc->error["ResponseError"]);
                echo trim($body);
            } else {
                print_r($doc->error);
            }

        }
		exit;

        $overview="Situs development prototype DAK";
        $doc->take("components","gov2nav");
    break;
    case "sso.krisna.systems":
        $overview="Situs development prototype SSO Node Krisna";
    break;
    case "sso.eplanning.id":
        $overview="Situs development prototype SSO Node Krisna";
    break;
    default:
        $overview="Situs development";
        $doc->take("components","gov2nav");
    break;
        
}
$doc->body("overview",$overview);
$self->content();
*/