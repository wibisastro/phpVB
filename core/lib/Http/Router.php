<?php
declare(strict_types=1);

namespace Gov2lib\Http;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Gov2lib\Contracts\RouterInterface;
use Gov2lib\Contracts\RouteResult;
use Gov2lib\Contracts\RouteStatus;
use InvalidArgumentException;
use SimpleXMLElement;

/**
 * Router class that wraps FastRoute and manages application routing.
 * 
 * This class handles:
 * - Loading routes from XML configuration files
 * - Parsing and registering routes with FastRoute
 * - Dispatching requests to appropriate handlers
 * - Resolving pageID, scriptID, and cmdID from URIs
 * - Mapping handlers to controller classes
 * 
 * The router supports both modern structured routes and legacy URL patterns
 * (slogin.php, ssignup, install.php, gov2login.php, HTML files, etc.)
 */
class Router implements RouterInterface
{
    /**
     * @var string The webroot prefix to strip from URIs
     */
    private string $webroot;

    /**
     * @var Dispatcher|null Cached FastRoute dispatcher
     */
    private ?Dispatcher $dispatcher = null;

    /**
     * @var array<string, string> Registered routes in format [method:uri => handler]
     */
    private array $routes = [];

    /**
     * @var string|null Resolved pageID from current request
     */
    private ?string $pageId = null;

    /**
     * @var string|null Resolved scriptID from current request
     */
    private ?string $scriptId = null;

    /**
     * @var string|null Resolved cmdID from current request
     */
    private ?string $cmdId = null;

    /**
     * Constructor for Router
     * 
     * @param string $webroot The webroot prefix (default: '/')
     */
    public function __construct(string $webroot = '/')
    {
        $this->webroot = rtrim($webroot, '/');
    }

    /**
     * Register a single route with the router
     * 
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $uri URI pattern with optional {param} placeholders
     * @param string $handler Handler class path
     * @throws InvalidArgumentException If method or uri is empty
     */
    public function addRoute(string $method, string $uri, string $handler): void
    {
        if (empty($method) || empty($uri)) {
            throw new InvalidArgumentException('Method and URI cannot be empty');
        }

        $method = strtoupper($method);
        $routeKey = "{$method}:{$uri}";
        $this->routes[$routeKey] = $handler;

        // Reset dispatcher cache when routes change
        $this->dispatcher = null;
    }

    /**
     * Dispatch a request and return the result
     * 
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @return RouteResult The dispatch result
     */
    public function dispatch(string $method, string $uri): RouteResult
    {
        $uri = $this->stripWebroot($uri);
        $method = strtoupper($method);

        // Resolve pageID, scriptID, cmdID from URI
        $this->resolveIds($uri);

        // Build dispatcher if not cached
        if ($this->dispatcher === null) {
            $this->dispatcher = $this->buildDispatcher();
        }

        $routeInfo = $this->dispatcher->dispatch($method, $uri);

        return $this->buildRouteResult($routeInfo, $uri);
    }

    /**
     * Load routes from an XML file
     * 
     * Expected XML format:
     * <routes>
     *   <route>
     *     <method>GET</method>
     *     <uri>/path/{var}</uri>
     *     <handler>App\module\className</handler>
     *   </route>
     * </routes>
     * 
     * @param string $xmlPath Path to the XML file
     * @throws InvalidArgumentException If file doesn't exist or XML is invalid
     */
    public function loadRoutesFromXml(string $xmlPath): void
    {
        if (!file_exists($xmlPath)) {
            throw new InvalidArgumentException("Route file not found: {$xmlPath}");
        }

        try {
            $xml = simplexml_load_file($xmlPath);
            if ($xml === false) {
                throw new InvalidArgumentException("Invalid XML in: {$xmlPath}");
            }

            foreach ($xml->route as $route) {
                $method = (string)$route->method;
                $uri = (string)$route->uri;
                $handler = (string)$route->handler;

                if (!empty($method) && !empty($uri) && !empty($handler)) {
                    $this->addRoute($method, $uri, $handler);
                }
            }
        } catch (\Exception $e) {
            throw new InvalidArgumentException(
                "Failed to load routes from {$xmlPath}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Load default routes from configuration directory
     * 
     * Looks for:
     * - apps/{pageID}/xml/route.xml (app-specific routes)
     * - core/config/route.xml (core framework routes)
     * 
     * @param string $configDir Base configuration directory
     */
    public function loadDefaultRoutes(string $configDir): void
    {
        $configDir = rtrim($configDir, '/');

        // Load core routes first
        $coreRouteFile = "{$configDir}/core/config/route.xml";
        if (file_exists($coreRouteFile)) {
            $this->loadRoutesFromXml($coreRouteFile);
        }

        // Load app-specific routes
        $appsDir = "{$configDir}/apps";
        if (is_dir($appsDir)) {
            $apps = glob("{$appsDir}/*/xml/route.xml");
            foreach ($apps as $routeFile) {
                $this->loadRoutesFromXml($routeFile);
            }
        }
    }

    /**
     * Resolve pageID, scriptID, and cmdID from URI
     * 
     * Parses structured URI: /{pageID}/{scriptID}/{cmdID}
     * Also handles legacy URL patterns.
     * 
     * @param string $uri The request URI
     */
    private function resolveIds(string $uri): void
    {
        $uri = trim($uri, '/');

        // Handle legacy URLs
        if ($this->isLegacyUrl($uri)) {
            $this->extractLegacyIds($uri);
            return;
        }

        // Parse structured URI: pageID/scriptID/cmdID
        $parts = explode('/', $uri);
        $this->pageId = !empty($parts[0]) ? $parts[0] : null;
        $this->scriptId = !empty($parts[1]) ? $parts[1] : null;
        $this->cmdId = !empty($parts[2]) ? $parts[2] : null;
    }

    /**
     * Check if URI matches legacy URL patterns
     * 
     * @param string $uri The URI to check
     * @return bool True if matches legacy pattern
     */
    private function isLegacyUrl(string $uri): bool
    {
        $patterns = [
            'slogin.php',
            'ssignup',
            'install.php',
            'index.php',
            'gov2login.php',
        ];

        foreach ($patterns as $pattern) {
            if (strpos($uri, $pattern) !== false) {
                return true;
            }
        }

        return preg_match('/\.html$/', $uri) === 1;
    }

    /**
     * Extract pageID, scriptID, cmdID from legacy URL patterns
     * 
     * @param string $uri The URI to parse
     */
    private function extractLegacyIds(string $uri): void
    {
        // Remove file extensions
        $uri = preg_replace('/\.(php|html)$/', '', $uri);

        $parts = explode('/', $uri);

        // Special handling for known legacy patterns
        if (in_array('slogin.php', $parts, true) || in_array('slogin', $parts, true)) {
            $this->pageId = 'slogin';
        } elseif (in_array('ssignup', $parts, true)) {
            $this->pageId = 'ssignup';
        } elseif (in_array('install.php', $parts, true) || in_array('install', $parts, true)) {
            $this->pageId = 'install';
        } elseif (in_array('gov2login.php', $parts, true) || in_array('gov2login', $parts, true)) {
            $this->pageId = 'gov2login';
        } else {
            $this->pageId = !empty($parts[0]) ? $parts[0] : null;
            $this->scriptId = !empty($parts[1]) ? $parts[1] : null;
            $this->cmdId = !empty($parts[2]) ? $parts[2] : null;
        }
    }

    /**
     * Strip webroot prefix from URI
     * 
     * @param string $uri The request URI
     * @return string The cleaned URI
     */
    private function stripWebroot(string $uri): string
    {
        if (!empty($this->webroot) && strpos($uri, $this->webroot) === 0) {
            $uri = substr($uri, strlen($this->webroot));
        }

        return '/' . ltrim($uri, '/');
    }

    /**
     * Build FastRoute dispatcher from registered routes
     * 
     * @return Dispatcher The FastRoute dispatcher
     */
    private function buildDispatcher(): Dispatcher
    {
        return \FastRoute\simpleDispatcher(function (RouteCollector $r) {
            foreach ($this->routes as $routeKey => $handler) {
                [$method, $uri] = explode(':', $routeKey, 2);
                $r->addRoute($method, $uri, $handler);
            }
        });
    }

    /**
     * Build RouteResult from FastRoute dispatcher result
     * 
     * @param array $routeInfo Result from dispatcher
     * @param string $uri The matched URI
     * @return RouteResult The route result
     */
    private function buildRouteResult(array $routeInfo, string $uri): RouteResult
    {
        $dispatcherStatus = $routeInfo[0] ?? Dispatcher::NOT_FOUND;
        $handler = $routeInfo[1] ?? '';
        $vars = $routeInfo[2] ?? [];

        $status = match ($dispatcherStatus) {
            Dispatcher::FOUND => RouteStatus::FOUND,
            Dispatcher::METHOD_NOT_ALLOWED => RouteStatus::METHOD_NOT_ALLOWED,
            default => RouteStatus::NOT_FOUND,
        };

        // Resolve controller from handler
        $controller = '';
        if ($status === RouteStatus::FOUND && is_string($handler)) {
            $controller = $this->resolveController($handler);
        }

        return new RouteResult(
            status: $status,
            handler: is_string($handler) ? $handler : '',
            controller: $controller,
            vars: is_array($vars) ? $vars : [],
            pageId: $this->pageId ?? '',
            scriptId: $this->scriptId ?? '',
            commandId: $this->cmdId ?? '',
        );
    }

    /**
     * Resolve controller class from handler string.
     * Maps Gov2lib handlers to their controller counterparts.
     */
    private function resolveController(string $handler): string
    {
        $handler = str_replace('/', '\\', $handler);

        if (str_starts_with($handler, 'Gov2lib')) {
            $parts = explode('\\', $handler);
            $handlerName = $parts[1] ?? '';

            return match ($handlerName) {
                'roleHandler' => 'Gov2lib\\role',
                'privilegeHandler' => 'Gov2lib\\privilege',
                'loginHandler' => 'Gov2lib\\login',
                'loginKeycloakHandler' => 'Gov2lib\\loginkeycloak',
                'optionsHandler' => 'Gov2lib\\options',
                'surveyHandler' => 'Gov2lib\\survey',
                default => 'Gov2lib\\index',
            };
        }

        // App handler: App\{pageID}\model\{class} → controller: App\{pageID}\{class}
        $parts = explode('\\', $handler);
        if (count($parts) >= 3) {
            $className = end($parts);
            $pageId = $this->pageId ?? ($parts[1] ?? '');
            return "App\\{$pageId}\\{$className}";
        }

        return $handler;
    }

    /**
     * Get the resolved pageID from the current request
     * 
     * @return string The pageID
     */
    public function getPageId(): string
    {
        return $this->pageId ?? '';
    }

    /**
     * Get the resolved scriptID from the current request
     * 
     * @return string The scriptID
     */
    public function getScriptId(): string
    {
        return $this->scriptId ?? '';
    }

    /**
     * Get the resolved cmdID from the current request
     * 
     * @return string The cmdID
     */
    public function getCommandId(): string
    {
        return $this->cmdId ?? '';
    }

    /**
     * Resolve pageID from a URI
     * 
     * @param string $uri The URI to parse
     * @return string The extracted pageID
     */
    public function resolvePageId(string $uri): string
    {
        $uri = $this->stripWebroot($uri);
        $this->resolveIds($uri);
        return $this->getPageId();
    }
}
