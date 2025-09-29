<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use PHPUnit\Framework\TestCase;
use Waffle\Attribute\Configuration;
use Waffle\Core\Security;
use Waffle\Core\System;
use Waffle\Router\Router;
use WaffleTests\Core\Helper\TestKernelWithConfig;

class SystemTest extends TestCase
{
    public function testBootInitializesAndRegistersRouter(): void
    {
        // 1. Setup
        // Create a mock for the Security class. We want to control its behavior.
        $securityMock = $this->createMock(Security::class);

        // We expect the 'analyze' method to be called twice during the boot process:
        // once for the Kernel and once for the Configuration object.
        $securityMock->expects($this->exactly(2))
            ->method('analyze');

        // Create a dummy Configuration object. The directory path doesn't need to be real
        // for this test, as we are mocking the dependencies that would use it.
        $testConfig = new Configuration(controller: 'app/Controllers');
        $testKernel = new TestKernelWithConfig($testConfig);

        // 2. Action
        // Instantiate the System class with our mock Security object.
        $system = new System($securityMock);
        $system->boot($testKernel);

        // 3. Assertions
        // We assert that the boot process correctly instantiated and registered a Router.
        $this->assertInstanceOf(Router::class, $system->router);
    }
}
