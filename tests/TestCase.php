<?php

declare(strict_types=1);

namespace Switon\Sharding\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Switon\Core\Attribute\Autowired;
use Switon\Core\InjectorInterface;
use Switon\Sharding\ShardingManagerInterface;
use Switon\Testing\Container;

/**
 * Base test case for Sharding tests.
 *
 * Provides common functionality for all Sharding tests using Container (as in real applications).
 * All dependencies are injected through Container's autowiring.
 */
abstract class TestCase extends BaseTestCase
{
    protected Container $container;
    protected InjectorInterface $injector;

    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected ShardingManagerInterface $shardingManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Use pre-configured test container (EventDispatcher is already registered)
        $this->container = new Container();

        // Disable event dispatching (Sharding tests typically don't need real event handling)
        // This is equivalent to NoOp behavior - events are collected but not dispatched
        $this->container->disableEventDispatching();

        // Inject all dependencies into TestCase via #[Autowired] attributes
        // Container automatically resolves ShardingManagerInterface to ShardingManager
        $this->injector = $this->container->get(InjectorInterface::class);
        $this->injector->inject($this);

        // Ensure properties are initialized
        if (!isset($this->shardingManager)) {
            $this->shardingManager = $this->container->get(ShardingManagerInterface::class);
        }
        if (!isset($this->eventDispatcher)) {
            $this->eventDispatcher = $this->container->get(EventDispatcherInterface::class);
        }
    }

    /**
     * Replace a dependency in container and recreate manager.
     *
     * Useful when a test needs a different mock (e.g., with expectations).
     *
     * @param string $interface The interface/class to replace
     * @param object $instance The new instance to use
     */
    protected function replaceDependency(string $interface, object $instance): void
    {
        // Remove old registration and instance
        $this->container->remove($interface);

        // Register new instance
        $this->container->set($interface, $instance);

        // If replacing EventDispatcher, update property and re-create ShardingManager
        if ($interface === EventDispatcherInterface::class) {
            $this->eventDispatcher = $instance;
            // Remove cached ShardingManager so it gets recreated with new EventDispatcher
            $this->container->remove(ShardingManagerInterface::class);
            // Get new instance which will be injected with new EventDispatcher
            $this->shardingManager = $this->container->get(ShardingManagerInterface::class);
        } elseif ($interface === ShardingManagerInterface::class) {
            // If replacing ShardingManager directly, just update property
            $this->shardingManager = $instance;
        } else {
            // For other dependencies, remove ShardingManager and recreate
            $this->container->remove(ShardingManagerInterface::class);
            $this->shardingManager = $this->container->get(ShardingManagerInterface::class);
        }
    }

    /**
     * Enable event dispatching.
     *
     * Useful when a test needs real event handling functionality.
     * By default, event dispatching is disabled (NoOp behavior).
     *
     * @return self For method chaining
     */
    protected function enableEventDispatching(): self
    {
        $this->container->enableEventDispatching();
        // Update property to reflect the change
        $this->eventDispatcher = $this->container->get(EventDispatcherInterface::class);
        return $this;
    }

    /**
     * Disable event dispatching.
     *
     * This is the default behavior - events are collected but not dispatched (NoOp behavior).
     *
     * @return self For method chaining
     */
    protected function disableEventDispatching(): self
    {
        $this->container->disableEventDispatching();
        // Update property to reflect the change
        $this->eventDispatcher = $this->container->get(EventDispatcherInterface::class);
        return $this;
    }
}
