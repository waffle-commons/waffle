<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use PHPUnit\Framework\TestCase;
use WaffleTests\Abstract\Helper\WebKernel;

final class AbstractKernelTest extends TestCase
{
    private WebKernel $kernel;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        // --- Test Environment Setup ---
        // We set the environment variable that the kernel will check.
        $_ENV['APP_ENV'] = 'dev';

        // --- Kernel Instantiation ---
        // We instantiate our dedicated test kernel.
        $this->kernel = new WebKernel();
    }

    #[\Override]
    protected function tearDown(): void
    {
        // Clean up environment variables to prevent test pollution.
        unset($_ENV['APP_ENV'], $_SERVER['REQUEST_URI']);
        parent::tearDown();
    }

    public function testHandleWithMatchingRouteRendersResponse(): void
    {
        // --- Test Condition ---
        // We simulate a web request to a valid URI.
        $_SERVER['REQUEST_URI'] = '/users';

        // --- Execution ---
        // We start the output buffer to capture the echoed JSON.
        ob_start();
        $this->kernel->handle();
        $output = ob_get_clean();
        if (!$output) {
            $output = '';
        }

        // --- Assertions ---
        // We assert that the output is the expected JSON response.
        static::assertJson($output);
        $expectedJson = '{
    "data": {
        "id": 1,
        "name": "John Doe"
    }
}';
        static::assertJsonStringEqualsJsonString($expectedJson, $output);
    }

    public function testHandleCatchesAndRendersThrowable(): void
    {
        // --- Test Condition ---
        // We simulate a request to our new, unambiguous error route.
        $_SERVER['REQUEST_URI'] = '/trigger-error';

        // --- Execution ---
        ob_start();
        $this->kernel->handle();
        $output = ob_get_clean();
        if (!$output) {
            $output = '';
        }

        // --- Assertions ---
        // We assert that the output contains the expected error message.
        static::assertJson($output);
        static::assertStringContainsString('"message": "An unexpected error occurred."', $output);
        static::assertStringContainsString('"error": "Something went wrong"', $output);
    }
}
