<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Commons\Contracts\Security\Exception\SecurityExceptionInterface;
use Waffle\Commons\Contracts\Security\SecurityInterface;
use Waffle\Core\System;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Core\Helper\SystemTestKernel;
use WaffleTests\TestsTrait\KernelFactoryTrait;

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
     * This test verifies that the boot process correctly performs the necessary security analyses
     * when everything is configured correctly.
     */
    public function testBootPerformsSecurityAnalysisOnSuccess(): void
    {
        // 1. Setup
        $this->createTestConfigFile(
            securityLevel: 2,
            controllerPath: $this->emptyControllerDir,
            servicePath: 'tests/src/Helper',
        );
        $testConfig = $this->createAndGetConfig(securityLevel: 2);

        // Create a mock Security instance
        $security = $this->createMock(SecurityInterface::class);
        $security->expects(static::atLeastOnce())->method('analyze');

        // Create a dummy Kernel for the test.
        $testKernel = new SystemTestKernel($testConfig);

        // 2. Action
        $system = new System($security);
        $system->boot($testKernel);

        // 3. Assertions
        // Verification is done via mock expectations
    }

    /**
     * This test ensures that if the security analysis fails during the boot process,
     * the system gracefully handles the exception and captures the JSON error output.
     */
    public function testBootHandlesSecurityException(): void
    {
        // 1. Setup
        $securityMock = $this->createMock(SecurityInterface::class);

        $exception = new class('Security analysis failed.') extends \Exception implements SecurityExceptionInterface {
            public function serialize(): array
            {
                return ['message' => $this->getMessage(), 'code' => $this->getCode()];
            }
        };

        $securityMock->method('analyze')->will($this->throwException($exception));

        $testConfig = $this->createAndGetConfig();
        $testKernel = new SystemTestKernel($testConfig);

        // 2. Action
        ob_start();
        $system = new System($securityMock);
        $system->boot($testKernel);
        $output = ob_get_clean() ?? '';

        // 3. Assertions
        static::assertJson($output, 'The output should be a valid JSON error response.');
        static::assertStringContainsString(
            'Security analysis failed.',
            $output,
            'The JSON output should contain the exception message.',
        );
    }
}
