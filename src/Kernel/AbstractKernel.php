<?php

declare(strict_types=1);

namespace Warp\Common\Kernel;

use Psr\Container\ContainerInterface;
use Warp\Container\CompositeContainer;
use Warp\Container\DefinitionAggregateInterface;
use Warp\Container\DefinitionContainer;
use Warp\Container\Factory\Reflection\ReflectionFactoryAggregate;
use Warp\Container\Factory\Reflection\ReflectionInvoker;
use Warp\Container\FactoryAggregateInterface;
use Warp\Container\FactoryContainer;
use Warp\Container\InvokerInterface;
use Warp\Container\ServiceProvider\ServiceProviderInterface;
use Warp\Container\ServiceProviderAggregateInterface;

abstract class AbstractKernel
{
    /**
     * @var ContainerInterface&ServiceProviderAggregateInterface&DefinitionAggregateInterface&FactoryAggregateInterface&InvokerInterface
     */
    protected ContainerInterface $container;

    protected bool $debugModeEnabled;

    /**
     * @param ContainerInterface|null $container
     * @param bool $debugModeEnabled
     */
    public function __construct(
        ?ContainerInterface $container = null,
        bool $debugModeEnabled = false
    ) {
        $this->container = $this->prepareContainer($container);
        $this->debugModeEnabled = $debugModeEnabled;

        $this->container->define(ContainerInterface::class, [$this, 'getContainer']);

        $this->container->define('kernel', static::class);
        $this->container->define(static::class, $this, true);
        $this->container->define('kernel.debug', [$this, 'isDebugModeEnabled']);

        foreach ($this->loadServiceProviders() as $serviceProvider) {
            $this->container->addServiceProvider($serviceProvider);
        }
    }

    /**
     * Returns the container.
     * @return ContainerInterface&ServiceProviderAggregateInterface&DefinitionAggregateInterface&FactoryAggregateInterface&InvokerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Running debug mode?
     * @return bool
     */
    public function isDebugModeEnabled(): bool
    {
        return $this->debugModeEnabled;
    }

    protected function enableDebugMode(bool $debug): void
    {
        $this->debugModeEnabled = $debug;
    }

    /**
     * @return iterable<ServiceProviderInterface|class-string<ServiceProviderInterface>>
     */
    protected function loadServiceProviders(): iterable
    {
        return [];
    }

    /**
     * @param ContainerInterface|null $container
     * @return ContainerInterface&ServiceProviderAggregateInterface&DefinitionAggregateInterface&FactoryAggregateInterface&InvokerInterface
     */
    private function prepareContainer(?ContainerInterface $container): ContainerInterface
    {
        if (
            $container instanceof ServiceProviderAggregateInterface &&
            $container instanceof DefinitionAggregateInterface &&
            $container instanceof FactoryAggregateInterface &&
            $container instanceof InvokerInterface
        ) {
            return $container;
        }

        $factory = new ReflectionFactoryAggregate();
        $invoker = new ReflectionInvoker();
        $definitionContainer = new DefinitionContainer($factory, $invoker);
        $factoryContainer = new FactoryContainer($factory, $invoker);

        return null === $container
            ? new CompositeContainer($definitionContainer, $factoryContainer)
            : new CompositeContainer($container, $definitionContainer, $factoryContainer);
    }
}
