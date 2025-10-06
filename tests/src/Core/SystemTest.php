<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Waffle\Attribute\Configuration;
use Waffle\Core\Security;
use Waffle\Core\System;
use Waffle\Exception\SecurityException;
use Waffle\Router\Router;
use WaffleTests\Core\Helper\TestKernelWithConfig;

#[CoversClass(System::class)]
final class SystemTest extends TestCase
{
    /**
     * This test verifies that the boot process correctly initializes the Router
     * and performs the necessary security analyses when everything is configured correctly.
     */
    public function testBootInitializesAndRegistersRouterOnSuccess(): void
    {
        // 1. Setup
        // Create a mock for the Security class. We want to control its behavior.
        $securityMock = $this->createMock(Security::class);

        // We expect the 'analyze' method to be called twice during the boot process:
        // once for the Kernel and once for the Configuration object.
        $securityMock->expects($this->exactly(2))->method('analyze');

        // Create a dummy Configuration object.
        $testConfig = new Configuration(controller: 'app/Controllers');
        $testKernel = new TestKernelWithConfig($testConfig);

        // 2. Action
        // Instantiate the System class with our mock Security object.
        $system = new System($securityMock);
        $system->boot($testKernel);

        // 3. Assertions
        // We assert that the boot process correctly instantiated and registered a Router.
        static::assertInstanceOf(Router::class, $system->router);
    }

    /**
     * This test ensures that if the security analysis fails during the boot process,
     * the system gracefully handles the exception, captures the JSON error output,
     * and does not register a router. This confirms that the internal try-catch
     * block in the boot() method is working as expected.
     */
    public function testBootHandlesSecurityExceptionAndDoesNotRegisterRouter(): void
    {
        // 1. Setup
        // Create a mock for the Security class that is configured to throw an exception.
        $securityMock = $this->createMock(Security::class);

        // We configure the 'analyze' method to throw a SecurityException. This simulates
        // a critical security validation failure during the framework's boot sequence.
        // We are testing the system's ability to catch this and respond gracefully.
        $securityMock
            ->method('analyze')
            ->will($this->throwException(new SecurityException('Security analysis failed.')));

        // Create a dummy Configuration and Kernel for the test.
        $testConfig = new Configuration(controller: 'app/Controllers');
        $testKernel = new TestKernelWithConfig($testConfig);

        // 2. Action
        // We start the output buffer to capture any `echo` calls made by the exception handler.
        ob_start();

        // Instantiate the System class and call the boot method, which should trigger the exception.
        $system = new System($securityMock);
        $system->boot($testKernel);

        // We retrieve the captured output and stop buffering.
        $output = ob_get_clean();
        if (!$output) {
            $output = '';
        }

        // 3. Assertions
        // First, we assert that the router was NOT registered because the security check failed.
        static::assertNull($system->router, 'Router should not be registered when a security exception occurs.');

        // Second, we verify that the system produced the expected JSON error response.
        static::assertJson($output, 'The output should be a valid JSON error response.');
        static::assertStringContainsString(
            'Security analysis failed.',
            $output,
            'The JSON output should contain the exception message.',
        );
    }
}
