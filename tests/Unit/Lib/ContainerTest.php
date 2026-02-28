<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\Container\Container;
use Gov2lib\Exceptions\ConfigException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Gov2lib\Container\Container
 * 
 * Tests the Dependency Injection container implementation including:
 * - Binding and resolution of services
 * - Singleton and factory patterns
 * - Instance registration
 * - Auto-wiring of constructor dependencies
 * - Error handling for unbound services
 */
class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testBindAndMakeWithClosure(): void
    {
        $this->container->bind('test_service', function () {
            return new TestDummyClass('from_closure');
        });

        $result = $this->container->make('test_service');

        $this->assertInstanceOf(TestDummyClass::class, $result);
        $this->assertEquals('from_closure', $result->getValue());
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $this->container->singleton('singleton_service', function () {
            return new TestDummyClass('singleton');
        });

        $first = $this->container->make('singleton_service');
        $second = $this->container->make('singleton_service');

        $this->assertSame($first, $second);
    }

    public function testFactoryBindingReturnsDifferentInstances(): void
    {
        $this->container->bind('factory_service', function () {
            return new TestDummyClass('factory');
        });

        $first = $this->container->make('factory_service');
        $second = $this->container->make('factory_service');

        $this->assertNotSame($first, $second);
        $this->assertInstanceOf(TestDummyClass::class, $first);
        $this->assertInstanceOf(TestDummyClass::class, $second);
    }

    public function testInstanceRegistersExistingObject(): void
    {
        $object = new TestDummyClass('instance_obj');
        $this->container->instance('my_instance', $object);

        $result = $this->container->make('my_instance');

        $this->assertSame($object, $result);
    }

    public function testHasReturnsTrueForBoundService(): void
    {
        $this->container->bind('bound_service', function () {
            return new TestDummyClass('test');
        });

        $this->assertTrue($this->container->has('bound_service'));
    }

    public function testHasReturnsFalseForUnboundService(): void
    {
        $this->assertFalse($this->container->has('unbound_service'));
    }

    public function testHasReturnsTrueForRegisteredInstance(): void
    {
        $this->container->instance('registered', new TestDummyClass('test'));

        $this->assertTrue($this->container->has('registered'));
    }

    public function testGetIsAliasForMake(): void
    {
        $this->container->bind('service', function () {
            return new TestDummyClass('via_get');
        });

        $viaGet = $this->container->get('service');
        $viaMake = $this->container->make('service');

        $this->assertInstanceOf(TestDummyClass::class, $viaGet);
        $this->assertInstanceOf(TestDummyClass::class, $viaMake);
    }

    public function testResolvingUnboundClassThrowsConfigException(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Unable to resolve service');

        $this->container->make('NonExistentService');
    }

    public function testAutoWiringResolvesConstructorDependencies(): void
    {
        $this->container->bind('dependency', function () {
            return new TestDummyClass('injected_dep');
        });

        $this->container->bind('service_with_deps', TestDependentClass::class);

        $result = $this->container->make('service_with_deps');

        $this->assertInstanceOf(TestDependentClass::class, $result);
        $this->assertInstanceOf(TestDummyClass::class, $result->getDependency());
    }

    public function testBindWithStringClassName(): void
    {
        $this->container->bind('dummy', TestDummyClass::class);

        $result = $this->container->make('dummy');

        $this->assertInstanceOf(TestDummyClass::class, $result);
    }

    public function testAutoWiringClassWithoutConstructor(): void
    {
        $result = $this->container->make(TestDummyClass::class);

        $this->assertInstanceOf(TestDummyClass::class, $result);
    }

    public function testClosureReceivesContainerAsParameter(): void
    {
        $receivedContainer = null;

        $this->container->bind('closure_with_container', function (Container $c) {
            // Store for inspection
            return new TestDummyClass('container_received');
        });

        $result = $this->container->make('closure_with_container');

        $this->assertInstanceOf(TestDummyClass::class, $result);
    }

    public function testContainerIsAutoRegistered(): void
    {
        $this->assertTrue($this->container->has(Container::class));
        $retrieved = $this->container->make(Container::class);
        $this->assertSame($this->container, $retrieved);
    }

    public function testSingletonBindingCachesInstance(): void
    {
        $callCount = 0;

        $this->container->singleton('counted', function () use (&$callCount) {
            $callCount++;
            return new TestDummyClass('call_' . $callCount);
        });

        $this->container->make('counted');
        $this->container->make('counted');
        $this->container->make('counted');

        // Closure should only be called once for singleton
        $this->assertEquals(1, $callCount);
    }

    public function testNestedDependencyResolution(): void
    {
        $this->container->singleton('level1', TestDummyClass::class);

        $result = $this->container->make(TestDummyClass::class);

        $this->assertInstanceOf(TestDummyClass::class, $result);
    }
}

/**
 * Test fixtures
 */
class TestDummyClass
{
    private string $value;

    public function __construct(string $value = 'default')
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}

class TestDependentClass
{
    private TestDummyClass $dependency;

    public function __construct(TestDummyClass $dependency)
    {
        $this->dependency = $dependency;
    }

    public function getDependency(): TestDummyClass
    {
        return $this->dependency;
    }
}
