<?php

declare(strict_types=1);

namespace WaffleTests\TestsTrait;

use Waffle\Abstract\AbstractKernel;use Waffle\Commons\Config\Config;use Waffle\Commons\Contracts\Container\ContainerInterface;use Waffle\Commons\Contracts\Core\KernelInterface;use Waffle\Commons\Security\Security;use Waffle\Core\Container as CoreContainer;use WaffleTests\Helper\MockContainer;
// The PSR-11 implementation
// The Security Decorator

trait KernelFactoryTrait
{

    /**
     * Creates the Core Container (Decorator) wrapping a real Commons Container.
     */
    protected function createRealContainer(int $level = 10): CoreContainer
    {
        $config = $this->createAndGetConfig(securityLevel: $level);
        $security = $this->createAndGetSecurity(config: $config);

        // 1. Create the raw PSR-11 container from the component
        $innerContainer = new MockContainer();

        // 2. Wrap it with the Core Container (Security Decorator)
        $container = new CoreContainer($innerContainer, $security);

        // Pre-populate key services into the INNER container via the wrapper's set() method
        $container->set(Config::class, $config);
        $container->set(Security::class, $security);

        return $container;
    }

    /**
     * Helper to get just the inner container if needed for the Kernel constructor in tests
     */
    protected function createInnerContainer(): MockContainer
    {
        return new MockContainer();
    }

    protected function createMockContainer(): ContainerInterface
    {
        return $this->createMock(ContainerInterface::class);
    }

    protected function createMockKernel(null|ContainerInterface $container = null): KernelInterface
    {
        $kernel = $this->createMock(AbstractKernel::class);
        $kernel->container = $container ?? $this->createMockContainer();
        return $kernel;
    }
}
