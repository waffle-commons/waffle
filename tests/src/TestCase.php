<?php

declare(strict_types=1);

namespace WaffleTests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Waffle\Abstract\AbstractKernel;
use Waffle\Core\Cli;
use Waffle\Core\Config;
use Waffle\Core\Container;
use Waffle\Core\Request;
use Waffle\Core\Security;
use Waffle\Enum\AppMode;
use Waffle\Enum\Failsafe;
use Waffle\Interface\ContainerInterface;
use Waffle\Interface\KernelInterface;

abstract class TestCase extends BaseTestCase
{
    protected string $testConfigDir = APP_ROOT . DIRECTORY_SEPARATOR . APP_CONFIG;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        // Create a temporary config directory for isolated testing
        if (!is_dir($this->testConfigDir)) {
            mkdir($this->testConfigDir, 0o777, true);
        }
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up the temporary config directory safely
        $this->cleanupTestConfig();
    }

    protected function cleanupTestConfig(): void
    {
        $dirToDelete = APP_ROOT . DIRECTORY_SEPARATOR . APP_CONFIG;
        if (is_dir($dirToDelete)) {
            $this->recursiveDelete($dirToDelete);
        }
    }

    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = array_diff(scandir($dir), ['.', '..']);

        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }

        rmdir($dir);
    }

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

        $yamlContentTest = <<<YAML
        waffle:
          test: %env(APP_DEBUG)%
          security:
            level: {$securityLevel}
          paths:
            # Point to the actual test helpers for controller/service resolution
            controllers: 'tests/src/Helper'
            services: 'tests/src/Helper'
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
        return new Container(security: $this->createAndGetSecurity(level: $level));
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

    protected function createRealRequest(int $level = 10, AppMode $isCli = AppMode::WEB): Request
    {
        return new Request(
            container: $this->createRealContainer(level: $level),
            cli: $isCli,
        );
    }

    protected function createRealCli(int $level = 10): Cli
    {
        return new Cli(
            container: $this->createRealContainer(level: $level),
            cli: AppMode::CLI,
        );
    }
}
