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
}
