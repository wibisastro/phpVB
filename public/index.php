<?php session_start();

//if (STAGE == 'dev') {

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
//header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header("Access-Control-Allow-Headers: Origin, Content-Type, X-Requested-With, X-Auth-Token");


//-----------
// Allow from any origin
/*
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
        // you want to allow, and if so:
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            // may also be using PUT, PATCH, HEAD etc
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

        exit(0);
    }

    echo "You have CORS!";
    */
//-----------
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
require_once "../core/init/index.php";
//print_r($response);
if ($request=='page') {
    $doc->render();
} else {
    header("Content-type:application/json");
    if (is_array($response)) {
        echo json_encode($response);
    } else {
        echo $response;
    }
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