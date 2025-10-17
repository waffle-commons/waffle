<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use Waffle\Interface\KernelInterface;
use WaffleTests\Abstract\Helper\WebKernel;
use WaffleTests\TestCase;

final class AbstractKernelTest extends TestCase
{
    private KernelInterface|null $kernel = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestConfigFile(securityLevel: 2);

        // We instantiate our test-specific WebKernel
        $this->kernel = new WebKernel(
            configDir: $this->testConfigDir,
            environment: 'test',
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
        // Simulate a web request to a valid URI.
        $_SERVER['REQUEST_URI'] = '/users';
        $_ENV['APP_ENV'] = 'dev';

        // Start the output buffer to capture the echoed JSON.
        ob_start();
        $this->kernel?->handle();
        $output = ob_get_clean() ?: '';

        // Assert that the output is the expected JSON response.
        static::assertJson($output);
        $expectedJson = '{"data":{"id":1,"name":"John Doe"}}';
        static::assertJsonStringEqualsJsonString($expectedJson, $output);
    }

    public function testHandleCatchesAndRendersThrowable(): void
    {
        // Simulate a request to our new, unambiguous error route.
        $_SERVER['REQUEST_URI'] = '/trigger-error';
        $_ENV['APP_ENV'] = 'dev';

        ob_start();
        $this->kernel?->handle();
        $output = ob_get_clean() ?: '';

        // Assert that the output contains the expected error message.
        static::assertJson($output);
        static::assertStringContainsString('"message": "An unexpected error occurred."', $output);
        static::assertStringContainsString('"error": "Something went wrong"', $output);
    }

    public function testHandleInCliMode(): void
    {
        // To isolate this test and ensure the CLI path is taken, we create a partial mock of the kernel.
        // We will only mock the `isCli` method to force it to return `true`,
        // while all other methods will retain their original implementation.
        $kernel = $this->getMockBuilder(WebKernel::class)
            ->setConstructorArgs([ // We must pass the original constructor arguments
                'configDir' => $this->testConfigDir,
                'environment' => 'test',
            ])
            ->onlyMethods(['isCli']) // We specify that only `isCli` will be a mock
            ->getMock();

        // We configure our mocked method to always return true for this test.
        $kernel->method('isCli')->willReturn(true);

        // Now, when we call handle(), it will be forced to take the CLI execution path.
        ob_start();
        $kernel->handle();
        $output = ob_get_clean() ?: '';

        // In the current implementation, the CLI path does nothing and produces no output.
        static::assertEmpty($output);
    }

    public function testBootLoadsEnvironmentVariables(): void
    {
        // --- Test Condition ---
        $envContent = "APP_ENV=test_boot\n# Add some comment\nANOTHER_VAR=waffle_test";
        $envPath = APP_ROOT . '/.env';
        file_put_contents($envPath, $envContent);

        // --- Execution ---
        $this->kernel?->boot();

        // --- Delete .env temp file ---
        unlink($envPath);

        // --- Assertions ---
        static::assertSame('test_boot', getenv('APP_ENV'));
        static::assertSame('waffle_test', getenv('ANOTHER_VAR'));
    }
}
