<?php

declare(strict_types=1);

namespace Gov2lib\Container;

/**
 * Abstract Service Provider
 *
 * Service providers are responsible for bootstrapping services and registering them
 * in the container. They follow a two-phase initialization pattern:
 *
 * 1. register() - Register service bindings (singletons, factories, etc.)
 * 2. boot() - Perform any initialization after all services are registered
 *
 * This pattern is useful for organizing bootstrap logic and handling inter-service
 * dependencies in a clean way.
 *
 * Example:
 * ```php
 * class DatabaseServiceProvider extends ServiceProvider
 * {
 *     public function register(): void
 *     {
 *         $this->container->singleton(
 *             DatabaseInterface::class,
 *             fn($c) => new MeekroDatabase($c->get(ConfigInterface::class))
 *         );
 *     }
 *
 *     public function boot(): void
 *     {
 *         // Run migrations, etc.
 *         $db = $this->container->get(DatabaseInterface::class);
 *         $db->runMigrations();
 *     }
 * }
 * ```
 *
 * @package Gov2lib\Container
 */
abstract class ServiceProvider
{
    /**
     * The container instance
     *
     * @var Container
     */
    protected Container $container;

    /**
     * Initialize the service provider
     *
     * @param Container $container The service container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register service bindings in the container
     *
     * This method is called first during bootstrap. Use it to register
     * service bindings, singletons, and factories.
     *
     * @return void
     */
    abstract public function register(): void;

    /**
     * Bootstrap services after all bindings are registered
     *
     * This method is called after all service providers have run their
     * register() methods. Use it for initialization logic that depends
     * on other services being registered.
     *
     * @return void
     */
    public function boot(): void
    {
        // Default no-op implementation
    }
}
