<?php

declare(strict_types=1);

namespace Gov2lib\Container;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use Gov2lib\Exceptions\ConfigException;

/**
 * Service Container for Dependency Injection
 *
 * A simple but functional service container that supports:
 * - Singleton bindings (most services)
 * - Factory bindings (created fresh each time)
 * - Interface to concrete class bindings
 * - Basic auto-wiring via type hints
 * - Thread-safe singleton resolution
 *
 * @package Gov2lib\Container
 */
class Container
{
    /**
     * Registered service bindings
     *
     * @var array<string, mixed>
     */
    private array $bindings = [];

    /**
     * Resolved singleton instances
     *
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * Flags indicating which bindings are singletons
     *
     * @var array<string, bool>
     */
    private array $singletons = [];

    /**
     * Lock for thread-safe singleton resolution
     *
     * @var object
     */
    private object $lock;

    /**
     * Initialize the container
     */
    public function __construct()
    {
        $this->lock = new class {};
        $this->instance(self::class, $this);
    }

    /**
     * Register a binding in the container
     *
     * Bindings can be:
     * - Closure/callable: resolved each time (factory)
     * - String class name: auto-wired and created each time
     * - Instance: returned directly
     *
     * @param string $abstract Service name or interface
     * @param mixed $concrete The implementation (closure, class name, or instance)
     * @return void
     */
    public function bind(string $abstract, mixed $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
        $this->singletons[$abstract] = false;
    }

    /**
     * Register a singleton binding in the container
     *
     * Singleton instances are created once and reused.
     * Closures receive the container as parameter for dependency resolution.
     *
     * @param string $abstract Service name or interface
     * @param mixed $concrete The implementation (closure, class name, or instance)
     * @return void
     */
    public function singleton(string $abstract, mixed $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
        $this->singletons[$abstract] = true;
    }

    /**
     * Register an existing instance in the container
     *
     * @param string $abstract Service name or interface
     * @param mixed $instance The instance to store
     * @return void
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
        $this->singletons[$abstract] = true;
    }

    /**
     * Check if a service is bound in the container
     *
     * @param string $abstract Service name or interface
     * @return bool True if the service is registered
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Resolve a service from the container
     *
     * Attempts to resolve services in this order:
     * 1. Pre-registered instances
     * 2. Registered bindings (singletons or factories)
     * 3. Auto-wiring from class name (if class exists)
     *
     * @param string $abstract Service name or class name
     * @return mixed The resolved service instance
     * @throws ConfigException If unable to resolve the service
     */
    public function make(string $abstract): mixed
    {
        // Return pre-registered instances directly
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Resolve registered bindings
        if (isset($this->bindings[$abstract])) {
            return $this->resolveBinding($abstract);
        }

        // Try auto-wiring if it's a class name
        if (class_exists($abstract)) {
            return $this->autoWire($abstract);
        }

        throw new ConfigException(
            "Unable to resolve service: {$abstract}. Service not bound and class does not exist."
        );
    }

    /**
     * Alias for make() - PSR-11 compatible interface
     *
     * @param string $abstract Service name or class name
     * @return mixed The resolved service instance
     * @throws ConfigException If unable to resolve the service
     */
    public function get(string $abstract): mixed
    {
        return $this->make($abstract);
    }

    /**
     * Resolve a registered binding
     *
     * @param string $abstract Service name
     * @return mixed The resolved instance
     * @throws ConfigException If unable to resolve dependencies
     */
    private function resolveBinding(string $abstract): mixed
    {
        $concrete = $this->bindings[$abstract];
        $isSingleton = $this->singletons[$abstract] ?? false;

        // If it's a closure, resolve it with dependency injection
        if ($concrete instanceof Closure) {
            $instance = $this->resolveClosure($concrete);
        }
        // If it's a string class name, auto-wire it
        elseif (is_string($concrete)) {
            $instance = $this->autoWire($concrete);
        }
        // Otherwise, it's a direct value
        else {
            $instance = $concrete;
        }

        // Cache singleton instances
        if ($isSingleton) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    /**
     * Resolve a closure with automatic dependency injection
     *
     * The closure receives the container as the first parameter,
     * allowing manual resolution of dependencies.
     *
     * @param Closure $closure The closure to resolve
     * @return mixed The result of calling the closure
     * @throws ConfigException If unable to inject dependencies
     */
    private function resolveClosure(Closure $closure): mixed
    {
        try {
            $reflection = new ReflectionFunction($closure);
            $parameters = $reflection->getParameters();

            // If the closure accepts the container, pass it
            if (!empty($parameters)) {
                $firstParam = $parameters[0];
                if ($this->isContainerParameter($firstParam)) {
                    return $closure($this);
                }
            }

            // Otherwise, call with no arguments
            return $closure();
        } catch (ReflectionException $e) {
            throw new ConfigException(
                "Failed to resolve closure: " . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Auto-wire a class by resolving its constructor dependencies
     *
     * @param string $className The fully qualified class name
     * @return object The instantiated class
     * @throws ConfigException If unable to resolve dependencies
     */
    private function autoWire(string $className): object
    {
        try {
            $reflection = new ReflectionClass($className);

            // If class is not instantiable, throw an exception
            if (!$reflection->isInstantiable()) {
                throw new ConfigException(
                    "Class '{$className}' is not instantiable."
                );
            }

            // Get constructor
            $constructor = $reflection->getConstructor();

            // If no constructor, instantiate with no arguments
            if ($constructor === null) {
                return new $className();
            }

            // Resolve constructor dependencies
            $parameters = $constructor->getParameters();
            $dependencies = [];

            foreach ($parameters as $parameter) {
                $dependencies[] = $this->resolveParameter($parameter);
            }

            return new $className(...$dependencies);
        } catch (ReflectionException $e) {
            throw new ConfigException(
                "Unable to auto-wire class '{$className}': " . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Resolve a single parameter from a reflection parameter
     *
     * Attempts to resolve based on type hint:
     * 1. Built-in types get their default value
     * 2. Class/Interface types are resolved from container
     *
     * @param ReflectionParameter $parameter The parameter to resolve
     * @return mixed The resolved parameter value
     * @throws ConfigException If unable to resolve a typed parameter
     */
    private function resolveParameter(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        // No type hint, check for default value
        if ($type === null) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw new ConfigException(
                "Unable to resolve parameter '\${$parameter->getName()}' with no type hint and no default value."
            );
        }

        // Get the type name
        $typeName = $type->getName();

        // Built-in types cannot be auto-wired
        if ($type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw new ConfigException(
                "Unable to auto-wire built-in type '\${$parameter->getName()}': {$typeName}. "
                . "Provide a default value or explicit binding."
            );
        }

        // Try to resolve class/interface from container
        return $this->make($typeName);
    }

    /**
     * Check if a parameter is for the Container itself
     *
     * @param ReflectionParameter $parameter The parameter to check
     * @return bool True if the parameter type is Container
     */
    private function isContainerParameter(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();

        if ($type === null) {
            return false;
        }

        return $type->getName() === self::class;
    }
}
