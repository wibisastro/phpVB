<?php

/********************************************************************
*	Date		: Thursday, 5 Okt, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI
*********************************************************************/
try {
    //--------layer modern Fase 2.5 (#6105): request dibungkus Gov2lib\Http\Request.
    //        Superglobals TIDAK dihapus — masih dipakai jalur legacy di bawah.
    $httpRequest = Gov2lib\Http\Request::createFromGlobals(trim((string)$config->webroot) ?: '/');

    $requestWith=explode(",", (string) $httpRequest->header('Accept', ''));

    if ($requestWith[0]=="application/json") {
        $request="ajax";
    } else {
        $request="page";
    }

    if (strstr((string) $httpRequest->header('User-Agent', ''),'curl')) {
        $requester="webservice";
    } else {
        $requester="browser";
    }

    $httpMethod = $httpRequest->method;

    $uri = $httpRequest->uri;

    //--------safety net exception tak tertangkap: styled page/JSON sesuai Accept,
    //        menggantikan fatal mentah PHP. Jalur $doc->exceptionHandler() legacy
    //        (string "Kode:Pesan") tetap jalan lebih dulu via try/catch existing.
    //        Error handler PHP (warning/notice) sengaja TIDAK didaftarkan —
    //        parity: kode legacy bergantung pada toleransi warning.
    $gov2ExceptionHandler = new Gov2lib\Http\ExceptionHandler($request === 'ajax', STAGE);
    set_exception_handler([$gov2ExceptionHandler, 'handle']);

    if (substr($uri, 0, 4) == "http") {
        $param=explode("/",$uri);
        $uri="/".$param[3];
    }

    if (false !== $pos = strpos($uri, '?')) {
        $parts=explode('/?',$uri);
        $_dummy=$parts[0] ?? '';
        $_qs=$parts[1] ?? '';
        parse_str($_qs, $qs);
        if (($qs['cmd'] ?? '')!='logout') {
            $_qs=implode('/',array_values($qs));
        } else {
            $_qs="";
        }
        $uri = substr($uri, 0, $pos);
        $uri=$uri.$_qs;
    }

    $uri = rawurldecode($uri);

    if ($config->webroot=="/") {
        $uriParts=explode("/",$uri);
    } else {
        $uriParts=explode("/",str_replace($config->webroot,"",$uri));
    }
    $param=$uriParts[0] ?? '';
    $pageID=$uriParts[1] ?? '';
    $scriptID=$uriParts[2] ?? '';
    $cmdID=$uriParts[3] ?? '';

    // App SSO-node di server ini — beo (#6161) men-set <ssoapp>beo</ssoapp> di
    // config.{stage}.xml; default gov2sso (paritas legacy). Path kontrak portal
    // (/slogin.php dkk) di-redirect DENGAN query string utuh: resolver
    // cmd=authorize&token= dipanggil portal via file_get_contents yang mengikuti
    // 302 — tanpa QS token hilang dan login 633 portal putus. /slogout.php tidak
    // redirect: pageID ditulis-ulang (paritas route legacy) agar cascade logout
    // tetap 1 round-trip per portal.
    $ssoApp = trim((string)($config->ssoapp ?? '')) ?: 'gov2sso';

    if ($pageID == 'ssignup' || $pageID == 'ssignup.php') {
        header("location: /{$ssoApp}/signup?".$_SERVER['QUERY_STRING']);
        exit;
    } elseif ($pageID == 'slogin' || $pageID == 'slogin.php') {
        header("location: /{$ssoApp}/slogin?".$_SERVER['QUERY_STRING']);
        exit;
    } elseif ($pageID == 'sprofile' || $pageID == 'sprofile.php') {
        header("location: /{$ssoApp}/sprofile?".$_SERVER['QUERY_STRING']);
        exit;
    } elseif ($pageID == 'slogout' || $pageID == 'slogout.php') {
        $pageID = $ssoApp;
    } elseif (!$pageID ||
        $pageID=="install.php" ||
        $pageID=="index.php" ||
        $pageID=="gov2login.php" ||
        strstr($pageID,'.html')) {
            if (strstr($pageID,'.html')) {
                $htmlFile=$pageID;
                $pageID="home";
            } elseif ($pageID=="gov2login.php") {
                $pageID="gov2login";
                $req=json_decode(stripslashes($_POST["req"] ?? ''),true);
                if (is_array($req)) { $_POST=array_merge($_POST,$req); }
            } elseif ($pageID=="install.php") {
                $pageID="gov2config";
                $req=json_decode(stripslashes($_POST["req"] ?? ''),true);
                if (is_array($req)) { $_POST=array_merge($_POST,$req); }
            } else {
                $pageID=trim($config->domain->{$_SERVER["SERVER_NAME"]});
                // Track that pageID was auto-resolved from config (not from URL).
                // Used below as a fallback: if user registered no route for `/`,
                // dispatcher will retry with `/{pageID}` (the standard app route).
                $pageIDFromConfig = true;
            }
    } elseif ($pageID=="login") {
        header("location: /".trim($config->domain->{$_SERVER["SERVER_NAME"]})."/$pageID");
    }

    $pageID=strip_tags($pageID);
    if (preg_match('/[^a-zA-Z0-9_.\-:]/', $pageID)) {
        throw new Exception('InvalidPageID:Accept only a-zA-Z0-9_');
    } else {
        $doc->pageID=$pageID;
        $default_router=__DIR__.'/../config/route.xml';
        $default_list=json_decode(json_encode(simplexml_load_file($default_router)),true);

        $router=__DIR__.'/../../apps/'.$pageID.'/xml/route.xml';

        if (file_exists($router)) {
            $config_file=__DIR__."/../../apps/$pageID/xml/config.xml";
            if (file_exists($config_file)) {
                $config = simplexml_load_file($config_file);
            }
            $list=json_decode(json_encode(simplexml_load_file($router)),true);
            if (is_array($list)) {
                if (is_array($list['route'][0])) {$list=$list['route'];}
                $merged_list=array_merge($default_list["route"],$list);
                //--------layer modern Fase 2.5 (#6105): dispatch via Gov2lib\Http\Router
                //        yang membungkus FastRoute. Route didaftarkan TANPA prefix
                //        webroot — Router yang strip webroot dari URI masuk
                //        (ekuivalen dgn pola lama: register webroot+uri, dispatch
                //        URI mentah). Handler yang cocok tetap jalur legacy di bawah.
                $gov2Router = new Gov2lib\Http\Router(trim((string)$config->webroot) ?: '/');
                foreach ($merged_list as $route) {
                    // Entri tak lengkap = dead route di pola lama (tak pernah
                    // match) — dilewati, jangan sampai mematahkan seluruh app
                    if (empty($route['method']) || empty($route['uri']) || !isset($route['handler'])) {
                        continue;
                    }
                    $gov2Router->addRoute($route['method'], $route['uri'], $route['handler']);
                }
                $routeResult = $gov2Router->dispatch($httpMethod, $uri);

                // Fallback: if pageID was auto-resolved from <domain> config
                // (user accessed bare `/` or `/index.php`) and no route matched,
                // retry with `/{pageID}`. This way apps don't need to register
                // `<uri>/</uri>` explicitly — the standard `/{pageID}` route
                // is used instead. Apps that DO register `/` keep precedence.
                if ($routeResult->status === Gov2lib\Contracts\RouteStatus::NOT_FOUND
                    && !empty($pageIDFromConfig)
                    && $pageID !== '') {
                    $fallbackUri = rtrim((string)($config->webroot ?? ''), '/') . '/' . $pageID;
                    $retryResult = $gov2Router->dispatch($httpMethod, $fallbackUri);
                    if ($retryResult->status === Gov2lib\Contracts\RouteStatus::FOUND) {
                        $uri = $fallbackUri;
                        $routeResult = $retryResult;
                    }
                }

                switch ($routeResult->status) {
                    case Gov2lib\Contracts\RouteStatus::NOT_FOUND:
                        throw new Exception("RouteNotFound:".$uri);
                    break;
                    case Gov2lib\Contracts\RouteStatus::METHOD_NOT_ALLOWED:
                        throw new Exception('MethodNotAllowed:'.$httpMethod);
                    break;
                    case Gov2lib\Contracts\RouteStatus::FOUND:
                        if ($httpMethod == "POST" && !$_POST) {
                            $_POST = json_decode(trim(file_get_contents('php://input')), true);
                        } elseif ($httpMethod == "GET") {
                            $vars = $routeResult->vars;
                            if (!isset($vars["cmd"])) {
                                if (($_GET['cmd'] ?? '')=='logout') {$vars["cmd"]="logout";}
                                else {$vars["cmd"]="";}
                            }
                        }
                        if (substr($routeResult->handler,0,7)=="Gov2lib") {
                            $handler = $routeResult->handler;
                            $handler=str_replace("/","\\",$handler);
                            list($_lib, $_handler)=explode("\\",$handler);
                            switch ($_handler) {
                                case "roleHandler":
                                    $controller = "Gov2lib\\role";
                                break;
                                case "privilegeHandler":
                                    $controller = "Gov2lib\\privilege";
                                break;
                                case "loginHandler":
                                    $controller = "Gov2lib\\login";
                                    if ($scriptID && $scriptID !== 'login' && empty($vars['cmd'])) {
                                        $vars['cmd'] = $scriptID;
                                    }
                                break;
                                case "loginKeycloakHandler":
                                    $controller = "Gov2lib\\loginkeycloak";
                                break;
                                case "optionsHandler":
                                    $controller = "Gov2lib\\options";
                                break;
                                case "surveyHandler":
                                    $controller = "Gov2lib\\survey";
                                break;
                                default:
                                    $controller = "Gov2lib\\index";
                            }

                            $className = $scriptID;

                        } else {
                            list($p,$d,$className)=explode("\\",$routeResult->handler);
                            $handler = "App\\".$pageID."\model\\".$className;
                            $controller = "App\\".$pageID."\\".$className;
                        }
                        $doc->body('className',$className);
                        $doc->body('scriptID',$scriptID);
                        $doc->body('cmdID',$cmdID);
                        if (class_exists($handler)) {
                            $self = new $handler;
                            $self->ses = new Gov2lib\gov2session($_POST);
                            $self->opt = new Gov2lib\gov2option();
                            $self->sur = new Gov2lib\gov2survey();
                        } else {
                            throw new Exception('Class/NameSpaceNotExist:'.$handler);
                        }
                    break;
                }
            } else {
                if (STAGE == 'dev' || STAGE == 'local') {
                    throw new Exception('InvalidRouterConfigFile:'.$router);
                } else {
                    throw new Exception('InvalidRouterConfigFile:'.$pageID);
                }

            }
        } else if ($pageID=="doc" || $pageID=="self") {
                throw new Exception('Forbidden:'.$pageID);
        } else {
            if (STAGE == 'dev' || STAGE == 'local') {
                throw new Exception('RouterConfigFileNotExist:'.$router);
            } else {
                throw new Exception('RouterConfigFileNotExist:'.$pageID);
            }
        }
    }
} catch (Exception $e) {
    $doc->exceptionHandler($e->getMessage());
}