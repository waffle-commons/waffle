<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use Waffle\Core\Container;
use Waffle\Core\Security;
use Waffle\Exception\Container\ContainerException;
use Waffle\Exception\Container\NotFoundException;
use WaffleTests\Core\Helper\ServiceA;
use WaffleTests\Core\Helper\ServiceB;
use WaffleTests\Core\Helper\ServiceC;
use WaffleTests\Core\Helper\ServiceD;
use WaffleTests\Core\Helper\ServiceE;
use WaffleTests\Core\Helper\Uninstantiable;
use WaffleTests\Core\Helper\WithPrimitive;
use WaffleTests\TestCase;

final class ContainerTest extends TestCase
{
    private Container $container;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createRealContainer(level: 8);
    }

    public function testCanResolveClassWithoutDependencies(): void
    {
        $this->container->set(ServiceA::class, ServiceA::class);

        $serviceA = $this->container->get(ServiceA::class);

        static::assertInstanceOf(ServiceA::class, $serviceA);
    }

    public function testCanResolveClassWithDependencies(): void
    {
        $this->container->set(ServiceA::class, ServiceA::class);
        $this->container->set(ServiceB::class, ServiceB::class);

        $serviceB = $this->container->get(ServiceB::class);

        static::assertInstanceOf(ServiceB::class, $serviceB);
        static::assertInstanceOf(ServiceA::class, $serviceB->serviceA);
    }

    public function testReturnsSameInstanceForSharedService(): void
    {
        $this->container->set(ServiceA::class, ServiceA::class);

        $serviceA1 = $this->container->get(ServiceA::class);
        $serviceA2 = $this->container->get(ServiceA::class);

        static::assertSame($serviceA1, $serviceA2);
    }

    public function testThrowsNotFoundExceptionForUnknownService(): void
    {
        static::expectException(NotFoundException::class);
        static::expectExceptionMessage('Service or class "NonExistentService" not found.');

        $this->container->get('NonExistentService');
    }

    public function testThrowsExceptionForCircularDependencies(): void
    {
        static::expectException(ContainerException::class);
        static::expectExceptionMessage(
            'Circular dependency detected while resolving service "WaffleTests\Core\Helper\ServiceD".',
        );

        $this->container->set(ServiceD::class, ServiceD::class);
        $this->container->set(ServiceE::class, ServiceE::class);

        // This should trigger the circular dependency detection
        $this->container->get(ServiceD::class);
    }

    public function testHasReturnsTrueForDefinedService(): void
    {
        $this->container->set(ServiceA::class, ServiceA::class);
        static::assertTrue($this->container->has(ServiceA::class));
    }

    public function testHasReturnsFalseForUndefinedService(): void
    {
        static::assertFalse($this->container->has('NonExistentService'));
    }

    public function testCanResolveWithFactoryClosure(): void
    {
        $this->container->set(ServiceA::class, fn(): ServiceA => new ServiceA());

        $serviceA = $this->container->get(ServiceA::class);

        static::assertInstanceOf(ServiceA::class, $serviceA);
    }

    public function testCanResolveNestedDependencies(): void
    {
        $this->container->set(ServiceA::class, ServiceA::class);
        $this->container->set(ServiceB::class, ServiceB::class);
        $this->container->set(ServiceC::class, ServiceC::class);

        $serviceC = $this->container->get(ServiceC::class);

        static::assertInstanceOf(ServiceC::class, $serviceC);
        static::assertInstanceOf(ServiceB::class, $serviceC->serviceB);
        static::assertInstanceOf(ServiceA::class, $serviceC->serviceB->serviceA);
    }

    public function testSecurityCheckIsCalledOnResolve(): void
    {
        $securityMock = $this->createMock(Security::class);
        $securityMock->expects($this->once())->method('analyze')->with(static::isInstanceOf(ServiceA::class));

        $container = new Container($securityMock);
        $container->get(ServiceA::class);
    }

    public function testThrowsExceptionForUninstantiableClass(): void
    {
        static::expectException(ContainerException::class);
        static::expectExceptionMessageMatches('/Class ".*" is not instantiable./');

        $this->container->get(Uninstantiable::class);
    }

    public function testThrowsExceptionForNonResolvablePrimitiveParameter(): void
    {
        static::expectException(ContainerException::class);
        static::expectExceptionMessage('Cannot resolve primitive parameter "_primitive".');

        $this->container->get(WithPrimitive::class);
    }
}
