<?php

declare(strict_types=1);

namespace Gov2lib;

use Gov2lib\Config\AppConfig;
use Gov2lib\Config\DatabaseConfig;
use Gov2lib\Container\Container;
use Gov2lib\Contracts\ConfigInterface;
use Gov2lib\Contracts\DatabaseInterface;
use Gov2lib\Contracts\RouterInterface;
use Gov2lib\Contracts\SessionInterface;
use Gov2lib\Contracts\RendererInterface;
use Gov2lib\Http\ExceptionHandler;
use Gov2lib\Http\Request;
use Gov2lib\Http\Response;
use Gov2lib\Http\Router;

/**
 * Application bootstrap class.
 *
 * Central entry point that wires together all framework components using DI.
 * This class replaces the procedural bootstrap in core/init/index.php while
 * maintaining full backward compatibility with existing code.
 *
 * Usage:
 *   $app = Application::create();
 *   $app->run();
 *
 * Or use the global helper:
 *   $app = app();
 *
 * During transition, legacy globals ($doc, $config, $self, etc.) are still
 * populated for backward compatibility with existing handler/model code.
 *
 * @author Wibisono Sastrodiwiryo <wibi@alumni.ui.ac.id>
 * @version 5.1.0 - Phase 2 Architecture
 */
class Application
{
    private static ?self $instance = null;

    private Container $container;
    private bool $booted = false;

    private function __construct()
    {
        $this->container = new Container();
    }

    /**
     * Create and boot the application.
     */
    public static function create(string $configDir = '', string $basePath = ''): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $app = new self();

        if (!$basePath) {
            $basePath = dirname(__DIR__, 2);
        }

        if (!$configDir) {
            $configDir = $basePath . '/core/config';
        }

        $app->container->instance('base_path', $basePath);
        $app->container->instance('config_dir', $configDir);

        $app->registerCoreServices($configDir, $basePath);

        self::$instance = $app;

        return $app;
    }

    /**
     * Get the application singleton instance.
     */
    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    /**
     * Get the DI container.
     */
    public function container(): Container
    {
        return $this->container;
    }

    /**
     * Resolve a service from the container.
     */
    public function make(string $abstract): mixed
    {
        return $this->container->make($abstract);
    }

    /**
     * Register core framework services.
     */
    private function registerCoreServices(string $configDir, string $basePath): void
    {
        // Config (singleton)
        $this->container->singleton(ConfigInterface::class, function () use ($configDir) {
            $serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
            return new AppConfig($configDir, $serverName);
        });

        // Exception Handler (singleton)
        $this->container->singleton(ExceptionHandler::class, function ($c) {
            $config = $c->make(ConfigInterface::class);
            $request = Request::createFromGlobals($config->getWebroot());
            return new ExceptionHandler($request->isAjax(), $config->getStage());
        });

        // Router (singleton)
        $this->container->singleton(RouterInterface::class, function ($c) {
            $config = $c->make(ConfigInterface::class);
            return new Router($config->getWebroot());
        });

        // Request (singleton per request lifecycle)
        $this->container->singleton(Request::class, function ($c) {
            $config = $c->make(ConfigInterface::class);
            return Request::createFromGlobals($config->getWebroot());
        });

        // Document (singleton, backward compat - existing $doc global)
        $this->container->singleton('document', function () {
            return new document();
        });
    }

    /**
     * Boot the application and set up legacy globals for backward compatibility.
     *
     * This method bridges the new architecture with existing code by populating
     * the global variables that handlers and models depend on ($doc, $config, etc.).
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Resolve core services
        $config = $this->container->make(ConfigInterface::class);
        $exceptionHandler = $this->container->make(ExceptionHandler::class);

        // Register global exception handler
        $exceptionHandler->register();

        // Populate legacy globals for backward compatibility
        $this->populateLegacyGlobals($config);

        $this->booted = true;
    }

    /**
     * Populate legacy global variables for backward compatibility.
     *
     * During the transition period, existing handlers and models still rely on
     * global $config, $doc, $publickey, etc. This method bridges old and new.
     */
    private function populateLegacyGlobals(ConfigInterface $config): void
    {
        // Legacy $config global (SimpleXMLElement)
        if ($config instanceof AppConfig) {
            $xmlConfig = $config->getXmlConfig();
            if ($xmlConfig !== null) {
                $GLOBALS['config'] = $xmlConfig;
            }
        }

        // Legacy $publickey global
        $GLOBALS['publickey'] = $config->get('app.publickey', 'c65ca73ce4c38dcec21151aa64f1590c');

        // Legacy STAGE constant
        if (!defined('STAGE')) {
            define('STAGE', $config->getStage());
        }

        // Error reporting based on stage
        $this->configureErrorReporting($config->getStage());
    }

    /**
     * Configure PHP error reporting based on application stage.
     */
    private function configureErrorReporting(string $stage): void
    {
        match ($stage) {
            'local', 'dev' => (function () {
                ini_set('display_errors', '1');
                $errorLevel = match ($_GET['error'] ?? '') {
                    'all' => E_ALL,
                    'warning' => E_ALL & ~E_NOTICE,
                    default => E_ALL & ~E_NOTICE & ~E_WARNING,
                };
                error_reporting($errorLevel);
            })(),
            'prod', 'build' => (function () {
                ini_set('display_errors', '0');
                error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
            })(),
            default => error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING),
        };
    }

    /**
     * Run the full application request cycle.
     *
     * This is the modern equivalent of the procedural code in core/init/index.php.
     * It can be used as an alternative to the legacy bootstrap once all handlers
     * are migrated.
     */
    public function run(): void
    {
        $this->boot();

        $config = $this->container->make(ConfigInterface::class);
        $request = $this->container->make(Request::class);
        $router = $this->container->make(RouterInterface::class);

        try {
            // Load routes
            $pageId = $router->resolvePageId($request->uri);
            $this->loadRoutes($router, $pageId, $config);

            // Dispatch
            $result = $router->dispatch($request->method, $request->uri);

            if (!$result->isFound()) {
                throw new Exceptions\NotFoundException("Route not found: {$request->uri}");
            }

            // Instantiate handler
            $handler = $result->handler;
            if (!class_exists($handler)) {
                throw new Exceptions\NotFoundException("Handler not found: {$handler}");
            }

            $self = new $handler();

            // Attach session, options, survey (same as existing route.php)
            $self->ses = new gov2session($_POST);
            $self->opt = new gov2option();
            $self->sur = new gov2survey();

            // Resolve command
            $cmd = $request->getCommand();
            if (!$cmd) {
                $cmd = 'index';
            }

            // Execute
            $response = null;
            $doc = $this->container->make('document');

            if (!is_array($doc->error)) {
                if (method_exists($self, $cmd)) {
                    $response = $self->{$cmd}($request->getPayload());
                } elseif ($cmd !== 'index') {
                    throw new \Exception("MethodNotExist: {$cmd}()");
                }
            } else {
                $response = $doc->responseAuth();
            }

            // Send response
            if ($request->isAjax()) {
                Response::json($response);
            } else {
                $doc->render();
            }
        } catch (\Throwable $e) {
            $exceptionHandler = $this->container->make(ExceptionHandler::class);
            $exceptionHandler->handle($e);
        }
    }

    /**
     * Load routes for a given page ID.
     */
    private function loadRoutes(RouterInterface $router, string $pageId, ConfigInterface $config): void
    {
        $basePath = $this->container->make('base_path');

        // Load default routes
        $defaultRoutes = $basePath . '/core/config/route.xml';
        if (file_exists($defaultRoutes)) {
            $router->loadRoutesFromXml($defaultRoutes);
        }

        // Load app-specific routes
        $appRoutes = $basePath . "/apps/{$pageId}/xml/route.xml";
        if (file_exists($appRoutes)) {
            $router->loadRoutesFromXml($appRoutes);
        }
    }

    /**
     * Reset the application singleton (for testing).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}

/**
 * Global helper to access the Application instance.
 */
function app(): ?Application
{
    return Application::getInstance();
}
