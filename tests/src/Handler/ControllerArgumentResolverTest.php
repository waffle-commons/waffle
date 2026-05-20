<?php

declare(strict_types=1);

namespace WaffleTests\Handler;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use stdClass;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Handler\ControllerArgumentResolver;
use Waffle\Service\ReflectionService;

#[CoversClass(ControllerArgumentResolver::class)]
#[AllowMockObjectsWithoutExpectations]
final class ControllerArgumentResolverTest extends TestCase
{
    public function testInjectsServerRequestInterfaceParameter(): void
    {
        // Note: container has NO entry for ServerRequestInterface (the SRI branch fires
        // before the container fallback, so we exercise it explicitly).
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $controller = new class {
            public function action(ServerRequestInterface $req): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $resolver = new ControllerArgumentResolver($container, new ReflectionService());
        $args = $resolver->resolve($controller, 'action', $request, []);

        static::assertCount(1, $args);
        static::assertSame($request, $args[0]);
    }

    public function testInjectsTypedServiceFromContainer(): void
    {
        $service = new stdClass();
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn(string $id): bool => $id === stdClass::class);
        $container->method('get')->with(stdClass::class)->willReturn($service);

        $controller = new class {
            public function action(stdClass $svc): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $resolver = new ControllerArgumentResolver($container, new ReflectionService());
        $args = $resolver->resolve($controller, 'action', $request, []);

        static::assertCount(1, $args);
        static::assertSame($service, $args[0]);
    }

    public function testCastsRouteParameterToBuiltinInt(): void
    {
        $container = $this->createStub(ContainerInterface::class);

        $controller = new class {
            public function action(int $id): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $resolver = new ControllerArgumentResolver($container, new ReflectionService());
        $args = $resolver->resolve($controller, 'action', $request, ['id' => '42']);

        static::assertSame([42], $args);
    }

    public function testFallsBackToDefaultValue(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $controller = new class {
            public function action(string $name = 'fallback'): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $resolver = new ControllerArgumentResolver($container, new ReflectionService());
        $args = $resolver->resolve($controller, 'action', $request, []);

        static::assertSame(['fallback'], $args);
    }

    public function testFallsBackToNullForNullableParameter(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $controller = new class {
            public function action(?stdClass $optional): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $resolver = new ControllerArgumentResolver($container, new ReflectionService());
        $args = $resolver->resolve($controller, 'action', $request, []);

        static::assertSame([null], $args);
    }

    public function testThrowsWhenParameterUnresolvable(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $controller = new class {
            public function action(stdClass $svc): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Argument "$svc"');

        new ControllerArgumentResolver($container, new ReflectionService())->resolve(
            $controller,
            'action',
            $request,
            [],
        );
    }
}
