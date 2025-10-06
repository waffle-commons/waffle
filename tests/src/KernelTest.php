<?php

declare(strict_types=1);

namespace WaffleTests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Waffle\Abstract\AbstractKernel;
use Waffle\Kernel;

/**
 * This test class is dedicated to validating the concrete Kernel class.
 *
 * Its primary purpose is to ensure that the class can be instantiated correctly
 * and that it properly extends its abstract parent. This simple validation is
 * essential for achieving 100% code coverage and confirming the basic integrity
 * of the framework's main entry point.
 */
#[CoversClass(Kernel::class)]
final class KernelTest extends TestCase
{
    /**
     * This test verifies that the Kernel class can be instantiated and that it
     * correctly inherits from AbstractKernel.
     */
    public function testKernelCanBeInstantiated(): void
    {
        $kernel = new Kernel();

        // Assert that the object is an instance of the concrete Kernel class.
        static::assertInstanceOf(Kernel::class, $kernel);

        // Assert that it also fulfills the contract of the AbstractKernel.
        static::assertInstanceOf(AbstractKernel::class, $kernel);
    }

    /**
     * This test is a placeholder to ensure that the boot() method can be called
     * without errors. It will be expanded upon if the concrete Kernel class
     * ever overrides the boot() method with its own specific logic.
     */
    public function testBootMethodIsCallable(): void
    {
        $kernel = new Kernel();
        $bootedKernel = $kernel->boot();

        // The primary assertion is that the boot method returns an instance of itself,
        // allowing for a fluent interface.
        static::assertInstanceOf(Kernel::class, $bootedKernel);
        static::assertSame($kernel, $bootedKernel);
    }
}
