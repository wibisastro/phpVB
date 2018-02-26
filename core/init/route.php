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
    
    $httpMethod = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];
    
    if (false !== $pos = strpos($uri, '?')) {$uri = substr($uri, 0, $pos);}
    $uri = rawurldecode($uri);

    if ($config->webroot=="/") {
        list($param,$pageID)=explode("/",$uri);
    } else {
        list($param,$pageID)=explode("/",str_replace($config->webroot,"",$uri));
    }

    if (!$pageID || $pageID=="index.php" || strstr($pageID,'.html')) {
        if (strstr($pageID,'.html')) {$htmlFile=$pageID;}
        $pageID="home";
    } 
    $doc->pageID=$pageID;

    $default_router=__DIR__.'/../config/route.xml';
    $default_list=json_decode(json_encode(simplexml_load_file($default_router)),true);

    $router=__DIR__.'/../../apps/'.$pageID.'/xml/route.xml';
    if (file_exists($router)) {
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
                        if (!isset($vars["cmd"])) {$vars["cmd"]="";}
                    }
                    if (substr($routeInfo[1],0,7)=="Gov2lib") {
                        $handler = $routeInfo[1];
                        $handler=str_replace("/","\\",$handler);
                        $controller = "Gov2lib\\index";
                    } else {
                        list($p,$d,$className)=explode("\\",$routeInfo[1]);
                        $handler = "App\\".$pageID."\model\\".$className;
                        $controller = "App\\".$pageID."\\".$className;
                        $doc->body('className',$className);
                    }

                    if (class_exists($handler)) {
                        $self = new $handler;
                    } else {
                        throw new Exception('Class/NameSpaceNotExist:'.$handler);
                    }  
                break;
            }
        }  else {
            throw new Exception('InvalidRouterConfigFile:'.$router);
        }
    } else {
        throw new Exception('RouterConfigFileNotExist:'.$router);
    }
} catch (Exception $e) {
    $doc->exceptionHandler($e->getMessage());
}