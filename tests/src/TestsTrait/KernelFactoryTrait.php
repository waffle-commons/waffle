<?php

declare(strict_types=1);

namespace WaffleTests\TestsTrait;

use Waffle\Abstract\AbstractKernel;
use Waffle\Commons\Contracts\Config\ConfigInterface;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Contracts\Core\KernelInterface;
use Waffle\Commons\Contracts\Security\SecurityInterface;
use Waffle\Core\Container as CoreContainer;
use WaffleTests\Helper\MockContainer;

// The PSR-11 implementation
// The Security Decorator

trait KernelFactoryTrait
{
    protected function createTestConfigFile(
        int $securityLevel = 10,
        string $controllerPath = 'tests/src/Helper/Controller',
        string $servicePath = 'tests/src/Helper/Service',
    ): void {
        $yamlContent = <<<YAML
        waffle:
          security:
            level: {$securityLevel}
          paths:
            controllers: '{$controllerPath}'
            services: '{$servicePath}'
        YAML;
        file_put_contents($this->testConfigDir . '/app.yaml', $yamlContent);

        $yamlContentTest = <<<YAML
        waffle:
          test_specific_key: true
        YAML;
        file_put_contents($this->testConfigDir . '/app_test.yaml', $yamlContentTest);
    }

    protected function createAndGetConfig(
        int $securityLevel = 10,
        string $controllerPath = 'tests/src/Helper/Controller',
        string $servicePath = 'tests/src/Helper/Service',
    ): ConfigInterface {
        $this->createTestConfigFile(
            securityLevel: $securityLevel,
            controllerPath: $controllerPath,
            servicePath: $servicePath,
        );

        $config = $this->createMock(ConfigInterface::class);
        $config
            ->method('getString')
            ->willReturnCallback(function ($key) use ($controllerPath, $servicePath) {
                return match ($key) {
                    'waffle.paths.controllers' => $controllerPath,
                    'waffle.paths.services' => $servicePath,
                    default => null,
                };
            });
        $config
            ->method('getInt')
            ->willReturnCallback(function ($key) use ($securityLevel) {
                return match ($key) {
                    'waffle.security.level' => $securityLevel,
                    default => null,
                };
            });

        return $config;
    }

    protected function createAndGetSecurity(int $level = 10, null|ConfigInterface $config = null): SecurityInterface
    {
        return $this->createMock(SecurityInterface::class);
    }

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
        $container->set(ConfigInterface::class, $config);
        $container->set(SecurityInterface::class, $security);

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
