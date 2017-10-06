<?php

/********************************************************************
*	Date		: Thursday, 5 Okt, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI 
*********************************************************************/

//--------routing
$router=__DIR__.'/routingTable.xml';

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
		}  else {
	        throw new Exception('InvalidRouterConfigFile');
		}
    } else {
        throw new Exception('NoRouterConfigFile');
    }
} catch (Exception $e) {
	echo $e->getMessage();
}	