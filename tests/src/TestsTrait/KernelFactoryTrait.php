<?php

declare(strict_types=1);

namespace WaffleTests\TestsTrait;

use Waffle\Abstract\AbstractKernel;
use Waffle\Core\Config;
use Waffle\Core\Container;
use Waffle\Core\Security;
use Waffle\Enum\Failsafe;
use Waffle\Interface\ContainerInterface;
use Waffle\Interface\KernelInterface;

trait KernelFactoryTrait
{
    protected function createTestConfigFile(int $securityLevel = 10): void
    {
        $yamlContent = <<<YAML
        waffle:
          security:
            level: {$securityLevel}
          paths:
            # Point to the actual test helpers for controller/service resolution
            controllers: 'tests/src/Helper'
            services: 'tests/src/Helper'
        YAML;
        file_put_contents($this->testConfigDir . '/app.yaml', $yamlContent);

        // Also create a test-specific environment file.
        $yamlContentTest = <<<YAML
        waffle:
          test_specific_key: true
        YAML;
        file_put_contents($this->testConfigDir . '/app_test.yaml', $yamlContentTest);
    }

    protected function createAndGetConfig(int $securityLevel = 10, Failsafe $failsafe = Failsafe::DISABLED): Config
    {
        $this->createTestConfigFile(securityLevel: $securityLevel);

        return new Config(
            configDir: $this->testConfigDir,
            environment: 'dev',
            failsafe: $failsafe,
        );
    }

    protected function createAndGetSecurity(int $level = 10, null|Config $config = null): Security
    {
        return new Security(cfg: $config ?? $this->createAndGetConfig(securityLevel: $level));
    }

    protected function createRealContainer(int $level = 10): Container
    {
        $config = $this->createAndGetConfig(securityLevel: $level);
        $security = $this->createAndGetSecurity(config: $config);
        $container = new Container(security: $security);

        // Pre-populate the container with the instances we've already created.
        $container->set(
            id: Config::class,
            concrete: $config,
        );
        $container->set(
            id: Security::class,
            concrete: $security,
        );

        return $container;
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
