<?php

declare(strict_types=1);

namespace WaffleTests;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Abstract\AbstractKernel;
use Waffle\Commons\Contracts\Config\ConfigInterface;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Contracts\Pipeline\MiddlewareStackInterface;
use Waffle\Commons\Contracts\Security\SecurityInterface;
use Waffle\Kernel;
use WaffleTests\AbstractTestCase as TestCase;

/**
 * This test class is dedicated to validating the concrete Kernel class.
 *
 * Its primary purpose is to ensure that the class can be instantiated correctly
 * (via constructor injection, ARCH-03) and that it properly extends its abstract
 * parent.
 */
#[CoversClass(Kernel::class)]
final class KernelTest extends TestCase
{
    private function makeKernel(): Kernel
    {
        return new Kernel(
            config: $this->createStub(ConfigInterface::class),
            container: $this->createStub(ContainerInterface::class),
            security: $this->createStub(SecurityInterface::class),
            middlewareStack: $this->createStub(MiddlewareStackInterface::class),
        );
    }

    /**
     * This test verifies that the Kernel class can be instantiated and that it
     * correctly inherits from AbstractKernel.
     */
    public function testKernelCanBeInstantiated(): void
    {
        $kernel = $this->makeKernel();

        // Assert that the object is an instance of the concrete Kernel class.
        static::assertInstanceOf(Kernel::class, $kernel);

        // Assert that it also fulfills the contract of the AbstractKernel.
        static::assertInstanceOf(AbstractKernel::class, $kernel);
    }

    /**
     * This test ensures that the boot() method can be called without errors and
     * returns the same instance (fluent interface).
     */
    public function testBootMethodIsCallable(): void
    {
        $kernel = $this->makeKernel();
        $bootedKernel = $kernel->boot();

        // The primary assertion is that the boot method returns an instance of itself,
        // allowing for a fluent interface.
        static::assertInstanceOf(Kernel::class, $bootedKernel);
        static::assertSame($kernel, $bootedKernel);
    }
}
