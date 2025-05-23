<?php

/********************************************************************
*	Date		: Sep, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI
*********************************************************************/
// use GuzzleHttp\Client;
//--------load configuration
require_once __DIR__.'/../config/index.php';

//--------autoloader

require_once __DIR__.'/../../vendor/autoload.php';

//--------load doc helper

$doc = new Gov2lib\document;

if ($config->error) {
    $doc->exceptionHandler($config->error);
}

//--------routing

require_once 'route.php';

//--------templating

require_once 'template.php';

/**
 * -----------------------------------
 * Load global helper functions.
 */
require_once __DIR__.'/../lib/helpers.php';

/*
//--------mailgun

use Mailgun\Mailgun;

$mailgun = new Mailgun($config->mailgun->apikey);
*/

// echo phpinfo();
// test_curl();
// test_guzzle();
// test_curl_terminal();
// exit;
try {
	if ($_POST) {
	    $cmd = $_POST['cmd'];
		$payload=$_POST;
	} else {
	    $cmd = !$vars["cmd"] ? "index" : $vars["cmd"];
		$payload=$vars;
	}
    //echo "c :".$controller;
    if (class_exists($controller)) {
        ${$pageID} = new $controller(); print_r($doc->error);
        if (!is_array($doc->error)) {
            if (method_exists(${$pageID},$cmd)) {
                $response=${$pageID}->$cmd($payload);
            } elseif ($cmd!="index") {
                throw new Exception('MethodNotExist: '.$cmd.'()');
            }
        } else {
            $response=$doc->responseAuth();
        }
	} else {
		throw new Exception('ControllerClassNotExist: '.$controller);
	}
} catch (Exception $e) {
    if (!isset($self) || strstr($e->getMessage(),"ControllerClassNotExist")) {
        $handler = "App\home\model\index";
        $self = new $handler;
        $controller = "App\home\index";
        if ($pageID != 'doc' && $pageID != 'self') {${$pageID} = new $controller();}
        $self->content();
    }
    $doc->exceptionHandler($e->getMessage());
}

/*
function test_curl()
{
    // $endpoint = "https://frankfurter.app/latest";
    $endpoint = "https://sso.gov2.web.id/slogin.php?cmd=authorize&token=favon1093icqojp18h5sdoq4b0";
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $endpoint,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: cURL'
        ]
    ));

    $response = curl_exec($curl);

    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_errno= curl_errno($curl);
    curl_close($curl);

    $resp = json_encode(json_decode($response));

    // echo "/etc/ssl/cacert.pem exists: " . file_exists("/etc/ssl/cacert.pem") . "<br>";
    echo "cURL HTTP STATUS :" . $http_status . "<br>";
    echo "cURL ErrNo :" . $curl_errno . "<br>";
    echo "cURL response :" . $resp . "<br>";

}


function test_guzzle()
{
    $jar = new \GuzzleHttp\Cookie\SessionCookieJar('PHPSESSID', true);
    $client = new Client([
        "timeout" => 20.0,
        "cookies" => $jar,
        "verify" =>false,
        "debug" => true,
    ]);

    $output=null;
    $retval=null;
    exec('openssl s_client -connect sso.gov2.web.id:443 -servername sso.gov2.web.id ', $output, $retval);
    echo "Returned with status $retval and output:\n";
    print_r($output);

    // $response = $client->request('GET', "https://frankfurter.app/latest");
    $response = $client->request('GET', 'https://sso.gov2.web.id/slogin.php?cmd=authorize&token=favon1093icqojp18h5sdoq4b0');
}


function test_curl_terminal()
{
    $url = "https://sso.gov2.web.id/slogin.php?cmd=authorize&token=33k8btvv9a3gkqm799c8s22djk";
    $output=null;
    $retval=null;
    // exec("curl $url 2>&1", $output, $retval);
    // $command = "curl $url 2>&1";
    $command = "curl -L -k --insecure \"$url\" 2>&1";
    echo "Executing command: <br>";
    echo "$ $command <br>";
    exec($command, $output, $retval);
    echo "Returned with status $retval and output: <br>";
    print_r($output);
}
*/