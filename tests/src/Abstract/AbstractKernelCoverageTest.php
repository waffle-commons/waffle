<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Waffle\Abstract\AbstractKernel;
use Waffle\Commons\Contracts\Config\ConfigInterface;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Contracts\Pipeline\MiddlewareStackInterface;
use Waffle\Commons\Contracts\Security\SecurityInterface;

#[CoversClass(AbstractKernel::class)]
#[AllowMockObjectsWithoutExpectations]
class AbstractKernelCoverageTest extends TestCase
{
    private function makeKernel(
        ConfigInterface $config,
        ContainerInterface $container,
        SecurityInterface $security,
    ): AbstractKernel {
        return new class(
            $config,
            $container,
            $security,
            $this->createStub(MiddlewareStackInterface::class),
            new NullLogger(),
        ) extends AbstractKernel {};
    }

    public function testCollaboratorsAreExposedFromConstruction(): void
    {
        // ARCH-03: the required collaborators are injected at construction, so the
        // kernel is never half-built. `system` stays null until configure() wires it.
        $config = $this->createMock(ConfigInterface::class);
        $container = $this->createMock(ContainerInterface::class);

        $kernel = $this->makeKernel($config, $container, $this->createMock(SecurityInterface::class));

        static::assertSame($config, $kernel->config);
        static::assertSame($container, $kernel->container);
        static::assertNull($kernel->system);
    }

    public function testConfigureWiresSystemWithValidDeps(): void
    {
        $configMock = $this->createMock(ConfigInterface::class);
        // null paths skip the ContainerFactory directory scan.
        $configMock->method('getString')->willReturn(null);

        $containerMock = $this->createMock(ContainerInterface::class);

        $kernel = $this->makeKernel($configMock, $containerMock, $this->createMock(SecurityInterface::class));

        $kernel->configure();

        static::assertSame($containerMock, $kernel->container);
        static::assertNotNull($kernel->system);
    }
}
