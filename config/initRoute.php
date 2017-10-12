<?php

/********************************************************************
*	Date		: Thursday, 5 Okt, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*********************************************************************/

try {
    if (file_exists($router)) {
        $list=simplexml_load_file($router);
		if (is_object($list)) {
			$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
				global $list;
	            foreach ($list->route as $route) {
					$r->addRoute($route->method, $route->uri, $route->handler);
				}
			});
			$httpMethod = $_SERVER['REQUEST_METHOD'];
			$uri = $_SERVER['REQUEST_URI'];
			if (false !== $pos = strpos($uri, '?')) {$uri = substr($uri, 0, $pos);}
			$uri = rawurldecode($uri);
			$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
			switch ($routeInfo[0]) {
			    case FastRoute\Dispatcher::NOT_FOUND:
			        throw new Exception(NOT_FOUND);
			    break;
			    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
			        $allowedMethods = $routeInfo[1];
			        throw new Exception('Method '.METHOD_NOT_ALLOWED.' is not Alowed');
			    break;
			    case FastRoute\Dispatcher::FOUND:
			        $handler = $routeInfo[1]."\model\index";
			        $vars = $routeInfo[2];
					$page = new $handler;
					/*
					//-----security compliance check
					if (is_array($_GET)) {
						while (list($key,$val)=each($_GET)) {
						    $val=strip_tags($val);
						    if (preg_match('/[^a-zA-Z0-9_.]/', $val)) {throw new Exception('IlegalQueryString');} 
						    else {$_GET[$key]=$val;}
						}
					}
					*/
			    break;
			}
		}  else {
	        throw new Exception('InvalidRouterConfigFile');
		}
    } else {
        throw new Exception('NoRouterConfigFile');
    }
} catch (Exception $e) {
	echo $e->getMessage();
}	