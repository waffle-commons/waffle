<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use Waffle\Interface\ContainerInterface;
use WaffleTests\Abstract\Helper\CliKernel;
use WaffleTests\Abstract\Helper\WebKernel;
use WaffleTests\AbstractTestCase as TestCase;

final class AbstractKernelTest extends TestCase
{
    private null|ContainerInterface $container = null;
    private null|WebKernel $kernel = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createRealContainer(level: 2);

        // Instantiate our test-specific WebKernel but do not boot it yet.
        $this->kernel = new WebKernel(
            configDir: $this->testConfigDir,
            environment: 'dev',
            container: $this->container,
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        unset($_ENV['APP_ENV'], $_SERVER['REQUEST_URI']);
        $this->kernel = null;

        parent::tearDown();
    }

    public function testHandleWithMatchingRouteRendersResponse(): void
    {
        // Arrange
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users';
        $_ENV['APP_ENV'] = 'dev';

        // Act: Boot the kernel now that the environment is ready.
        $this->kernel?->boot()->configure();

        ob_start();
        $this->kernel?->handle();
        $output = ob_get_clean() ?? '';

        // Assert
        static::assertJson($output);
        $expectedJson = '{"data":{"id":1,"name":"John Doe"}}';
        static::assertJsonStringEqualsJsonString($expectedJson, $output);
    }

    public function testHandleCatchesAndRendersThrowable(): void
    {
        // Arrange
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/trigger-error';
        $_ENV['APP_ENV'] = 'dev';

        // Act: Boot the kernel.
        $this->kernel?->boot()->configure();

        ob_start();
        $this->kernel?->handle();
        $output = ob_get_clean() ?? '';

        // Assert
        static::assertJson($output);
        static::assertStringContainsString('"message": "An unexpected error occurred."', $output);
        static::assertStringContainsString('"error": "Something went wrong"', $output);
    }

    public function testHandleInCliMode(): void
    {
        // Arrange: Create a partial mock of the kernel.
        $_ENV['APP_ENV'] = 'test';

        // Instantiate our test-specific WebKernel but do not boot it yet.
        $kernel = new CliKernel(
            configDir: $this->testConfigDir,
            environment: 'dev',
            container: $this->container,
        );

        // Act: Manually boot and configure the kernel now that the mock is set up.
        $kernel->boot()->configure();

        ob_start();
        $kernel->handle();
        $output = ob_get_clean() ?? '';

        // Assert
        static::assertEmpty($output);
    }

    public function testBootLoadsEnvironmentVariables(): void
    {
        // Arrange
        $envContent = "APP_TEST=test_boot\nANOTHER_VAR=waffle_test";
        $envPath = APP_ROOT . '/.env';
        file_put_contents($envPath, $envContent);

        // Act
        $this->kernel?->boot();

        // Teardown
        unlink($envPath);

        // Assert
        static::assertSame('test_boot', getenv('APP_TEST'));
        static::assertSame('waffle_test', getenv('ANOTHER_VAR'));
    }
}
