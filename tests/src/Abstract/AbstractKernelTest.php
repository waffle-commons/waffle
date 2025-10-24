<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use Waffle\Core\Constant;
use Waffle\Interface\ContainerInterface;
use WaffleTests\Abstract\Helper\WebKernel;
use WaffleTests\AbstractTestCase as TestCase;

final class AbstractKernelTest extends TestCase
{
    private null|ContainerInterface $container = null;
    private null|WebKernel $kernel = null;
    private null|string $originalAppEnv = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->originalAppEnv = $_ENV[Constant::APP_ENV] ?? null; // Backup APP_ENV

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
        // Restore original APP_ENV
        $_ENV[Constant::APP_ENV] = $this->originalAppEnv;
        if ($this->originalAppEnv === null) {
            unset($_ENV[Constant::APP_ENV]);
        }
        $this->kernel = null;

        parent::tearDown();
    }

    public function testHandleWithMatchingRouteRendersResponse(): void
    {
        // Arrange
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users';
        $_ENV[Constant::APP_ENV] = 'dev';

        // Act: Boot the kernel now that the environment is ready.
        $this->kernel?->boot()->configure();

        ob_start();
        $this->kernel?->handle();
        $output = ob_get_clean() ?? '';

        // Assert
        static::assertJson($output, 'Output was: ' . $output);
        $expectedJson = '{"data":{"id":1,"name":"John Doe"}}';
        static::assertJsonStringEqualsJsonString($expectedJson, $output);
    }

    public function testHandleCatchesAndRendersThrowable(): void
    {
        // Arrange
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/trigger-error';
        $_ENV[Constant::APP_ENV] = 'dev';

        // Act: Boot the kernel.
        $this->kernel?->boot()->configure();

        ob_start();
        $this->kernel?->handle();
        $output = ob_get_clean() ?? '';

        // Assert
        static::assertJson($output, 'Output was: ' . $output);
        static::assertStringContainsString('"message": "An unexpected error occurred."', $output);
        static::assertStringContainsString('"error": "Something went wrong"', $output);
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
