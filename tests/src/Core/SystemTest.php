<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use PHPUnit\Framework\Attributes\CoversClass;use Waffle\Commons\Security\Security;use Waffle\Core\System;use Waffle\Exception\SecurityException;use Waffle\Router\Router;use WaffleTests\AbstractTestCase as TestCase;use WaffleTests\Core\Helper\SystemTestKernel;use WaffleTests\TestsTrait\KernelFactoryTrait;
// No longer mocking Security
// Added for config helper

#[CoversClass(System::class)]
final class SystemTest extends TestCase
{
    // Use trait to easily create config files
    use KernelFactoryTrait;

    private string $emptyControllerDir = 'empty_controllers_for_system_test';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure the empty directory exists for the success test
        if (!is_dir($this->testConfigDir . DIRECTORY_SEPARATOR . $this->emptyControllerDir)) {
            mkdir($this->testConfigDir . DIRECTORY_SEPARATOR . $this->emptyControllerDir, 0777, true);
        }
    }

    #[\Override]
    protected function tearDown(): void
    {
        // Clean up the empty directory
        if (is_dir($this->testConfigDir . DIRECTORY_SEPARATOR . $this->emptyControllerDir)) {
            rmdir($this->testConfigDir . DIRECTORY_SEPARATOR . $this->emptyControllerDir);
        }
        parent::tearDown();
    }

    /**
     * This test verifies that the boot process correctly initializes the Router
     * and performs the necessary security analyses when everything is configured correctly.
     */
    public function testBootInitializesAndRegistersRouterOnSuccess(): void
    {
        // 1. Setup
        // Create a specific config file pointing to an empty controller dir
        // Use a very low security level (e.g., 2) to prevent exceptions on helpers
        $this->createTestConfigFile(
            securityLevel: 2, // Use a very low level
            controllerPath: $this->emptyControllerDir, // Point to empty dir
            servicePath: 'tests/src/Helper', // Keep services for container resolution if needed
        );
        // Load the config normally using the AbstractTestCase helper
        $testConfig = $this->createAndGetConfig(securityLevel: 2);

        // Create a REAL Security instance using this low-level config
        $security = new Security($testConfig);

        // Create a dummy Kernel for the test.
        // Ensure the kernel uses the correct config which has the modified paths
        $testKernel = new SystemTestKernel($testConfig);

        // 2. Action
        // Instantiate the System class with the real Security object.
        $system = new System($security);
        $system->boot($testKernel); // This internally calls configure -> ContainerFactory -> System::boot

        // 3. Assertions
        // We assert that the boot process correctly instantiated and registered a Router,
        // even though no controller routes were found.
        static::assertNotNull($system->router, 'Router should be created even with no controllers found.');
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
        $testConfig = $this->createAndGetConfig(); // Use default config here
        $testKernel = new SystemTestKernel($testConfig);

        // 2. Action
        // We start the output buffer to capture any `echo` calls made by the exception handler.
        ob_start();

        // Instantiate the System class and call the boot method, which should trigger the exception.
        $system = new System($securityMock);
        $system->boot($testKernel);

        // We retrieve the captured output and stop buffering.
        $output = ob_get_clean() ?? '';

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
