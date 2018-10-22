<?php

require_once "../core/init/index.php";

//if (STAGE == 'dev') {
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
/*
} else {

    $http_origin = "localhost";
    
    $allowed_domains = array(
      'localhost',
      'api.kl2.web.id',
    );

    if (in_array($http_origin, $allowed_domains))
    {  
        $http_origin="http://".$http_origin;
        header("Access-Control-Allow-Origin: $http_origin");
        header('Access-Control-Allow-Headers: Content-type');
        header('Access-Control-Allow-Methods: GET');
    } 
}
*/

//print_r($response);
if ($request=='page') {
    $doc->render();
} else {
    header("Content-type:application/json");
    echo json_encode($response); 
}
/*
if ($doc->counter) {
//---- counter dihasilkan dari request page yang sukses
//     atau dari request ajax yang error
    if ($request=='page') {
    //---- response non-ajax yang tidak error
	   $doc->render();
    } else {
    //---- semua response ajax yang error
        $response=$doc->response("is-danger","");
        header("HTTP/1.1 422 Query Fails");
        header("Content-type:application/json");
        echo json_encode($response);
    }
} else {
//---- semua response ajax yang tidak error 
//---- response ajax dirender di fn masing2
    header("Content-type:application/json");
    echo json_encode($response);
}
*/