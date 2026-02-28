<?php

namespace Gov2lib;

use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Cookie\CookieJar;

/**
 * HTTP API client for Gov2
 */
class api extends document
{
    /**
     * Initialize API client
     */
    public function __construct(string $_dsn = "master")
    {
        parent::__construct();
        $this->client = new \GuzzleHttp\Client(['verify' => false]);
    }

    /**
     * Make GET request to API endpoint
     */
    public function getdata(string $url, bool $authorization = false, ?string $bearer_token = null, bool $include_cookies = false): ?array
    {
        global $doc, $request;
        $headers = [
            'headers' => [
                'Accept' => 'application/json'
            ],
        ];

        if ($authorization) {
            $token = $bearer_token ?? ($_SESSION['tokenBearer'] ?? null);
            $headers['headers']['Authorization'] = 'Bearer ' . $token;
        }

        if ($include_cookies) {
            $cookie = [
                'Gov2Session' => $_COOKIE['Gov2Session'] ?? null
            ];
            $jar = CookieJar::fromArray($cookie, $_SERVER['SERVER_NAME']);
            $headers['cookies'] = $jar;
        }

        try {
            $res = $this->client->request('GET', $url, $headers);
            if ($res) {
                return json_decode($res->getBody(), 1);
            } else {
                throw new \Exception('GetFail:' . $url);
            }
        } catch (ClientException $e) {
            $doc->error("RequestError", Psr7\str($e->getRequest()));
            $doc->error("ResponseError", Psr7\str($e->getResponse()));
            $_response = json_decode($e->getResponse()->getBody(), 1);
            if ($request === 'ajax') {
                return $_response;
            }
        } catch (\Exception $e) {
            $_response = json_decode($e->getMessage(), 1);
            if ($request === 'ajax') {
                return $_response;
            }
        }

        return null;
    }

    /**
     * Make POST request to API endpoint
     */
    public function putdata(string $url, array $data, bool $authorization = false, ?string $bearer_token = null, bool $include_cookies = false): ?string
    {
        global $doc, $config, $self;

        $headers = [
            'form_params' => [
                "cmd" => $data['cmd'],
                "data" => $data['data']
            ],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ];

        if ($authorization) {
            $token = $bearer_token ?? ($_SESSION['tokenBearer'] ?? null);
            $headers['headers']['Authorization'] = 'Bearer ' . $token;
        }

        if ($include_cookies) {
            $cookie = [
                'Gov2Session' => $_COOKIE['Gov2Session'] ?? null
            ];
            $jar = CookieJar::fromArray($cookie, $_SERVER['SERVER_NAME']);
            $headers['cookies'] = $jar;
        }

        try {
            $res = $this->client->request('POST', $url, $headers);
            $_result = $res->getBody();
        } catch (ClientException $e) {
            $doc->error("ErrKeyRequest", Psr7\str($e->getRequest()));
            $doc->error("ErrKeyResponse", Psr7\str($e->getResponse()));
            if ($e->hasResponse()) {
                $_result = $e->getResponse()->getBody();
            }
        } catch (\Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }

        return $_result ?? null;
    }

    /**
     * Authenticate using API key
     */
    public function authenticate(string $public): ?string
    {
        global $doc, $config;
        try {
            $res = $this->client->request('POST', trim($config->platform->apikey), [
                'form_params' => [
                    "cmd" => "apikey_status",
                    "public" => trim($public)
                ]
            ]);
            return $res->getBody();
        } catch (ClientException $e) {
            $doc->error("ErrKeyRequest", Psr7\str($e->getRequest()));
            $doc->error("ErrKeyResponse", Psr7\str($e->getResponse()));
        } catch (\Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }

        return null;
    }

    /**
     * Authorize API access
     */
    public function authorize(string $_publickey = ""): void
    {
        global $doc, $publickey, $self;
        try {
            if ($_SESSION['token'] ?? false) {
                $this->authorizeToken($_SESSION['token']);
            } else {
                $bearerToken = $this->getBearerToken();
                if ($bearerToken) {
                    $this->authorizeToken($bearerToken);
                } else {
                    throw new \Exception('NoSession:Service ' . $_SERVER['SERVER_NAME'] . ' membutuhkan session');
                }
            }
        } catch (\Exception $e) {
            $this->exceptionHandler($e->getMessage());
        }
    }

    /**
     * Create new session
     */
    public function createSession(array $vars): ?array
    {
        global $self, $doc, $publickey, $request;

        $token = ($vars['data']['apikey'] ?? false) ? $vars['data']['token'] : $vars['token'];

        if (!$token) {
            unset($_SESSION['token']);
            $response["class"] = 'is-info';
            $response["notification"] = "Hapus session berhasil, silahkan refresh browser";
        } else {
            try {
                $decoded = $this->decodeJWT($token);
                $json = json_decode($this->authenticate($decoded->key));
                if ($json->comm == 'ok') {
                    $domain = match (STAGE) {
                        'kpu', 'drc', 'prod', 'build', 'cybergl' => "https://" . $json->domain,
                        default => "http://" . $json->domain,
                    };

                    if ($domain == $decoded->aud) {
                        $key = file_get_contents($decoded->aud . "/gov2api.html");
                        if (trim($key) == $decoded->key) {
                            $_SESSION['token'] = $token;
                        } else {
                            throw new \Exception('IlegalAudience:Domain pengakses bukan Audience kami');
                        }
                    } else {
                        throw new \Exception('InvalidDomain:Domain/Key pengakses tidak valid');
                    }
                } else {
                    throw new \Exception('UnlistedDomain:Domain pengakses belum terdaftar');
                }

                $response["class"] = "is-success";
                $response["notification"] = "Exp: " . date("d-m-Y H:i:s", $decoded->exp);
                $response["callback"] = "refreshBrowser";
            } catch (\Exception $e) {
                $response["class"] = "is-danger";
                $response["notification"] = $e->getMessage();
            }
        }

        return $response ?? null;
    }

    /**
     * Get Authorization header
     */
    private function getAuthorizationHeader(): ?string
    {
        $headers = null;

        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        return $headers;
    }

    /**
     * Get bearer token from Authorization header
     */
    private function getBearerToken(): ?string
    {
        $headers = $this->getAuthorizationHeader();
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Authorize using JWT token
     */
    private function authorizeToken(string $token): void
    {
        global $publickey, $self, $doc;
        try {
            $decoded = $this->decodeJWT($token);
            try {
                if (in_array($self->className, $decoded->dataset) || in_array("all", $decoded->dataset)) {
                    $self->token = $decoded;
                } else {
                    throw new \Exception("Unauthorized: Token Anda hanya untuk Dataset " . implode(',', $decoded->dataset));
                }
            } catch (\Exception $e) {
                $this->exceptionHandler($e->getMessage());
            }
        } catch (\Exception $e) {
            $doc->error("ErrToken", $e->getMessage());
        }
    }

    /**
     * Decode JWT token
     */
    private function decodeJWT(string $token): object
    {
        global $publickey;
        return \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($publickey, 'HS256'));
    }
}
