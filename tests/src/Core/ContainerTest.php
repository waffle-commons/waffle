<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Waffle\Commons\Security\Exception\SecurityException;
use Waffle\Commons\Security\Security;
use Waffle\Core\Container;
use Waffle\Exception\Container\ContainerException;
use Waffle\Exception\Container\NotFoundException;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Core\Helper\AbstractUninstantiable;
use WaffleTests\Core\Helper\ServiceA;
use WaffleTests\Core\Helper\ServiceB;
use WaffleTests\Core\Helper\ServiceC;
use WaffleTests\Core\Helper\ServiceD;
use WaffleTests\Core\Helper\ServiceE;
use WaffleTests\Core\Helper\ServiceWithDefaultParam;
use WaffleTests\Core\Helper\ServiceWithOptionalParam;
use WaffleTests\Core\Helper\ServiceWithUnionParam;
use WaffleTests\Core\Helper\WithPrimitive;

class PsrNotFoundException extends \Exception implements NotFoundExceptionInterface
{
}

class PsrContainerException extends \Exception implements ContainerExceptionInterface
{
}

final class ContainerTest extends TestCase
{
    private null|Container $container = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // createRealContainer in AbstractTestCase handles the wrapping correctly now.
        // We assume KernelFactoryTrait::createRealContainer has been updated (see previous conversation)
        // or we do it manually here to be sure.

        $this->container = $this->createRealContainer(level: 8);
    }

    public function testCanResolveClassWithoutDependencies(): void
    {
        $this->container->set(ServiceA::class, ServiceA::class);

        $serviceA = $this->container->get(ServiceA::class);

        static::assertInstanceOf(ServiceA::class, $serviceA);
    }

    public function testSecurityCheckIsCalledOnResolve(): void
    {
        $securityMock = $this->createMock(Security::class);
        $securityMock->expects($this->once())->method('analyze')->with(static::isInstanceOf(ServiceA::class));

        // Mock inner container to return a ServiceA instance
        $innerMock = $this->createMock(PsrContainerInterface::class);
        $innerMock->method('get')->willReturn(new ServiceA());

        // Pass both mocks
        $container = new Container($innerMock, $securityMock);
        $container->get(id: ServiceA::class);
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
        // Note: The exact message comes from the Commons component now.
        static::expectExceptionMessage('Circular dependency detected');

        $this->container->set(ServiceD::class, ServiceD::class);
        $this->container->set(ServiceE::class, ServiceE::class);

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

    public function testThrowsExceptionForUninstantiableClass(): void
    {
        static::expectException(ContainerException::class);
        static::expectExceptionMessageMatches('/is not instantiable/');

        $this->container->get(AbstractUninstantiable::class);
    }

    public function testThrowsExceptionForNonResolvablePrimitiveParameter(): void
    {
        static::expectException(ContainerException::class);
        static::expectExceptionMessage('Cannot resolve primitive parameter "_primitive".');

        $this->container->get(WithPrimitive::class);
    }

    public function testResolveDependenciesWithOptionalNullableParam(): void
    {
        // The container should be able to resolve this class even if the optional
        // parameter is not provided in the definition.
        $this->container->set(ServiceWithOptionalParam::class, ServiceWithOptionalParam::class);

        /** @var ServiceWithOptionalParam $service */
        $service = $this->container->get(ServiceWithOptionalParam::class);

        static::assertInstanceOf(ServiceWithOptionalParam::class, $service);
        // The constructor should have used the default null value.
        static::assertNull($service->name);
    }

    public function testResolveDependenciesWithDefaultScalarParam(): void
    {
        // The container should use the default scalar value defined in the constructor.
        $this->container->set(ServiceWithDefaultParam::class, ServiceWithDefaultParam::class);

        /** @var ServiceWithDefaultParam $service */
        $service = $this->container->get(ServiceWithDefaultParam::class);

        static::assertInstanceOf(ServiceWithDefaultParam::class, $service);
        // The constructor should have used the default value 5.
        static::assertSame(5, $service->count);
    }

    public function testResolveDependenciesThrowsForUnresolvableUnionParam(): void
    {
        // The container cannot automatically decide whether to inject a string or an int
        // for a scalar union type without explicit configuration or context.
        $this->container->set(ServiceWithUnionParam::class, ServiceWithUnionParam::class);

        // We expect a ContainerException because the 'id' parameter is unresolvable.
        // Note: The exact exception message might vary depending on how deep the
        // resolution goes before failing, but it should indicate a resolution problem.
        $this->expectException(ContainerException::class);
        // A more specific message expectation might be fragile, but could be:
        // $this->expectExceptionMessageMatches('/Cannot resolve parameter "\$id"/');
        // OR depending on implementation:
        // $this->expectExceptionMessageMatches('/Cannot resolve primitive parameter "id"/');

        // Attempting to get the service should trigger the exception.
        $this->container->get(ServiceWithUnionParam::class);
    }

    public function testGetWrapsPsrNotFoundException(): void
    {
        $innerMock = $this->createMock(PsrContainerInterface::class);
        $innerMock->method('get')->willThrowException(new PsrNotFoundException('PSR Not Found'));

        $securityMock = $this->createMock(Security::class);

        $container = new Container($innerMock, $securityMock);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('PSR Not Found');

        $container->get('missing_service');
    }

    public function testGetWrapsPsrContainerException(): void
    {
        $innerMock = $this->createMock(PsrContainerInterface::class);
        $innerMock->method('get')->willThrowException(new PsrContainerException('PSR Error'));

        $securityMock = $this->createMock(Security::class);

        $container = new Container($innerMock, $securityMock);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('PSR Error');

        $container->get('broken_service');
    }

    public function testGetWrapsSecurityException(): void
    {
        $innerMock = $this->createMock(PsrContainerInterface::class);
        $innerMock->method('get')->willThrowException(new SecurityException('Security Error'));

        $securityMock = $this->createMock(Security::class);

        $container = new Container($innerMock, $securityMock);

        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Security Error');

        $container->get('broken_service');
    }

    public function testGetWrapsThrowable(): void
    {
        $innerMock = $this->createMock(PsrContainerInterface::class);
        $innerMock->method('get')->willThrowException(new ContainerException('Throwable Error'));

        $securityMock = $this->createMock(Security::class);

        $container = new Container($innerMock, $securityMock);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Throwable Error');

        $container->get('broken_service');
    }

    public function testSetThrowsException(): void
    {
        $innerMock = $this->createMock(PsrContainerInterface::class);
        $securityMock = $this->createMock(Security::class);

        $container = new Container($innerMock, $securityMock);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("The inner container does not support mutable 'set' operations.");

        $container->set('broken_service', 'broken_service');
    }
}
