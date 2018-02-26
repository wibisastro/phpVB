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
			        throw new Exception("RouteNotFound");
			    break;
			    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
			        $allowedMethods = $routeInfo[1];
			        throw new Exception('Method '.METHOD_NOT_ALLOWED.' is not Alowed');
			    break;
			    case FastRoute\Dispatcher::FOUND:
			        $vars = $routeInfo[2];
					if (substr($routeInfo[1],0,7)=="Gov2lib") {
						$handler = $routeInfo[1];
                        $handler=str_replace("/","\\",$handler);
					} else {
						$handler = "App\\".$routeInfo[1]."\model\index";
					}
                    $page = new $handler;
			    break;
			}
		}  else {
	        throw new Exception('InvalidRouterConfigFile');
		}
    } else {
        throw new Exception('NoRouterConfigFile');
    }
} catch (Exception $e) {
    $doc->exceptionHandler($e->getMessage());
}	