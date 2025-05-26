<?php namespace App\gov2login\model;

use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;

class login extends \Gov2lib\document {
    function __construct () {
        global $doc;
		$this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $doc->body("className",$this->className);
    }
    
    function createSession ($_vars) {
        global $config, $self;
        $loginPath = (string)$config->platform->loginpath;
        if(!$loginPath) {
            $loginPath = '/slogin.php';
        }
        try {
            if ($_vars['token']) {
                $_content_url = $config->platform->ssonode.$loginPath."?cmd=authorize&token=".$_vars['token'];
                
                $_authorized=file_get_contents($_content_url);
                // $_authorized= $this->authorizeToken($_vars['token']);
                
                
                $_data=json_decode($_authorized,1);
                // var_dump($_content_url);
                // echo "<br>";
                // var_dump($_data);exit;

                if (!$_data['error']) {
                    /*
                    $landingpage=$_SESSION["landingpage"];
                    $servicepage=$_SESSION["servicepage"];
                    */
                    $_token["account_id"]=(int)$_data['account_id'];
                    $_token["fullname"]=$_data['fullname'];
                    $_token["facebook"]=$_data['facebook'];
                    $_token["email"]=$_data['email'];
                    $_token["photourl"]=$_data['photourl'];
                    $_token["ssokey"]=$_data['ssokey'];

                    // var_dump($_data);exit;
                    $self->ses->sesSave($_token,1);
                    /*
                //    if ($servicepage) {$_SESSION["servicepage"]=$servicepage;}
                    if (!$landingpage) {$landingpage=$doc->pageID;}
//                    header("Location: /$landingpage");
                    header("Location: /");
                    exit;
                    */
                } else {
                    throw new \Exception('Error:'.$_data['error']);
                }
            } else {
                throw new \Exception('NoToken:Something went wrong with the login system');
            }
        } catch (\Exception $e) {
			$this->exceptionHandler($e->getMessage());
        }
    }
    
    function createPipe ($_vars) {
        global $config, $self;
        $loginPath = (string)$config->platform->loginpath;
        if(!$loginPath) {
            $loginPath = '/slogin.php';
        }
        try {
            if ($_vars['token']) {
                
                $_content_url = $_vars['service']."=".$_vars['token'];

                $jar = new \GuzzleHttp\Cookie\SessionCookieJar('PHPSESSID', true);
                
                $client = new \GuzzleHttp\Client(['cookies' => $jar]);
                
                $res = $client->request('GET', $_content_url);
                
                if ($res) {
                    header("Location: /gov2pipe/pipedin");
                    exit;
                } else {
                    throw new \Exception('Error:'.$_data['error']);
                }
            } else {
                throw new \Exception('NoToken:Something went wrong with the login system');
            }
        } catch (\Exception $e) {
			$this->exceptionHandler($e->getMessage());
        }
    }

    function createKeyCloakSession(&$provider, $grant = "authorization_code")
    {
        global $self;
        $options = array();
        if ($grant === 'refresh_token') {
            $options[$grant] = $self->ses->val[$grant];
        } else {
            $options['code'] = $_GET['code'];
        }

        try {
            // Try to get an access token using the authorization code grant.
            $accessToken = $provider->getAccessToken($grant, $options);
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            // Failed to get the access token
//            print_r($provider);
            exit($e->getMessage());
        }
        // Using the access token, we may look up details about the
        // resource owner.
        $resourceOwner = $provider->getResourceOwner($accessToken);
        $resourceOwnerArray = $resourceOwner->toArray();

        $_SESSION["access_token"]     = $accessToken->getToken();
        $_SESSION["refresh_token"]    = $accessToken->getRefreshToken();

//        $_token["access_token"]     = $accessToken->getToken();
//        $_token["refresh_token"]    = $accessToken->getRefreshToken();
        $_token["expired_in"]       = date('Y-m-d H:i:s', $accessToken->getExpires());
        $_token["account_id"]       = $resourceOwnerArray['preferred_username'];
        $_token["fullname"]         = $resourceOwnerArray['name'];
        $_token["email"]            = $resourceOwnerArray['email'];
        $_token["sub"]              = $resourceOwnerArray['sub'];
        $_token["family_name"]      = $resourceOwnerArray['family_name'];
        $_token["email_verified"]   = $resourceOwnerArray['email_verified'];

        $_token["photourl"]         = null;
        $_token["ssokey"]           = null;
        $self->ses->sesSave($_token, 1);
    }


    /**
     * Extract any cookies found from the cookie file. This function expects to get
     * a string containing the contents of the cookie file which it will then
     * attempt to extract and return any cookies found within.
     *
     * @param string $string The contents of the cookie file.
     *
     * @return array The array of cookies as extracted from the string.
     *
     */
    function extractCookies($string) {

        $lines = explode(PHP_EOL, $string);

        foreach ($lines as $line) {

            $cookie = array();

            // detect httponly cookies and remove #HttpOnly prefix
            if (substr($line, 0, 10) == '#HttpOnly_') {
                $line = substr($line, 10);
                $cookie['httponly'] = true;
            } else {
                $cookie['httponly'] = false;
            }

            // we only care for valid cookie def lines
            if($line[0] != '#' && substr_count($line, "\t") == 6) {

                // get tokens in an array
                $tokens = explode("\t", $line);

                // trim the tokens
                $tokens = array_map('trim', $tokens);

                // Extract the data
                $cookie['domain'] = $tokens[0]; // The domain that created AND can read the variable.
                $cookie['flag'] = $tokens[1];   // A TRUE/FALSE value indicating if all machines within a given domain can access the variable.
                $cookie['path'] = $tokens[2];   // The path within the domain that the variable is valid for.
                $cookie['secure'] = $tokens[3]; // A TRUE/FALSE value indicating if a secure connection with the domain is needed to access the variable.

                $cookie['expiration-epoch'] = $tokens[4];  // The UNIX time that the variable will expire on.
                $cookie['name'] = urldecode($tokens[5]);   // The name of the variable.
                $cookie['value'] = urldecode($tokens[6]);  // The value of the variable.

                // Convert date to a readable format
                $cookie['expiration'] = date('Y-m-d h:i:s', $tokens[4]);

                // Record the cookie.
                $cookies[] = $cookie;
            }
        }

        return $cookies;
    }

    public function authorizeToken($token)
    {
        global $config, $self;

        $curl = curl_init();

        $ssoLoginPath = (string)$config->platform->loginpath;
        if(!$ssoLoginPath) {
            $ssoLoginPath = '/slogin.php';
        }

        $query = [
            'cmd' => 'authorize',
            'token' => $token
        ];

        $endpoint = (string)$config->platform->ssonode . $ssoLoginPath . '?' . http_build_query($query);

        curl_setopt_array($curl, array(
            CURLOPT_URL => $endpoint,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_TIMEOUT => 5,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
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

        if ($curl_errno > 0) {
            $self->exceptionHandler("cURL Failed: " . $curl_errno .' -> ' . $http_status .' GET ' .  $endpoint);
        }

        $resp = json_encode(json_decode($response));
        /*
        if ($curl_errno > 0 || $resp == "null") {
            echo "Go2Login@authorizeToken:" . $endpoint . "<br>";
            echo "cURL HTTP STATUS :" . $http_status . "<br>";
            echo "cURL ErrNo :" . $curl_errno . "<br>";
            echo "cURL response :" . $resp . "<br>";
            exit;
        }
        */

        return $response;
    }

}
?>