<?php

/********************************************************************
*	Date		: Thursday, 5 Okt, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI
*********************************************************************/
try {
    $requestWith=explode(",",$_SERVER['HTTP_ACCEPT']);

    if ($requestWith[0]=="application/json") {
        $request="ajax";
    } else {
        $request="page";
    }

    if (strstr($_SERVER['HTTP_USER_AGENT'],'curl')) {
        $requester="webservice";
    } else {
        $requester="browser";
    }

    $httpMethod = $_SERVER['REQUEST_METHOD'];

    $uri = $_SERVER['REQUEST_URI'];

    if (substr($uri, 0, 4) == "http") {
        $param=explode("/",$uri);
        $uri="/".$param[3];
    }

    if (false !== $pos = strpos($uri, '?')) {
        list($_dummy,$_qs)=explode('/?',$uri);
        parse_str($_qs, $qs);
        if ($qs['cmd']!='logout') {
            $_qs=implode('/',array_values($qs));
        } else {
            $_qs="";
        }
        $uri = substr($uri, 0, $pos);
        $uri=$uri.$_qs;
    }

    $uri = rawurldecode($uri);

    if ($config->webroot=="/") {
        list($param,$pageID,$scriptID,$cmdID)=explode("/",$uri);
    } else {
        list($param,$pageID,$scriptID,$cmdID)=explode("/",str_replace($config->webroot,"",$uri));
    }

    if ($pageID == 'ssignup' || $pageID == 'ssignup.php') {
        header("location: /gov2sso/signup?".$_SERVER['QUERY_STRING']);
    } elseif ($pageID == 'slogin' || $pageID == 'slogin.php') {
        header("location: /gov2sso/slogin/".$_GET['client']);
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
                $req=json_decode(stripslashes($_POST["req"]),true);
                $_POST=array_merge($_POST,$req);
            } elseif ($pageID=="install.php") {
                $pageID="gov2config";
                $req=json_decode(stripslashes($_POST["req"]),true);
                $_POST=array_merge($_POST,$req);
            } else {
                $pageID=trim($config->domain->{$_SERVER["SERVER_NAME"]});
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
                $dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
                    global $merged_list, $config;
                    foreach ($merged_list as $route) {
                        $r->addRoute($route['method'], $config->webroot.$route['uri'], $route['handler']);
                    }
                });
                $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
                switch ($routeInfo[0]) {
                    case FastRoute\Dispatcher::NOT_FOUND:
                        throw new Exception("RouteNotFound:".$uri);
                    break;
                    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                        $allowedMethods = $routeInfo[1];
                        throw new Exception('MethodNotAllowed:'.$httpMethod);
                    break;
                    case FastRoute\Dispatcher::FOUND:
                        if ($httpMethod == "POST" && !$_POST) {
                            $_POST = json_decode(trim(file_get_contents('php://input')), true);
                        } elseif ($httpMethod == "GET") {
                            $vars = $routeInfo[2];
                            if (!isset($vars["cmd"])) {
                                if ($_GET['cmd']=='logout') {$vars["cmd"]="logout";}
                                else {$vars["cmd"]="";}
                            }
                        }
                        if (substr($routeInfo[1],0,7)=="Gov2lib") {
                            $handler = $routeInfo[1];
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

                        } else {
                            list($p,$d,$className)=explode("\\",$routeInfo[1]);
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