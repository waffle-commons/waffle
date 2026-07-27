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
use Waffle\Exception\ValidationException;
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

    public function testCastsRouteParameterToBuiltinNegativeInt(): void
    {
        $container = $this->createStub(ContainerInterface::class);

        $controller = new class {
            public function action(int $id): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $resolver = new ControllerArgumentResolver($container, new ReflectionService());
        $args = $resolver->resolve($controller, 'action', $request, ['id' => '-7']);

        static::assertSame([-7], $args);
    }

    public function testAcceptsLeadingPlusSignForBuiltinInt(): void
    {
        // FILTER_VALIDATE_INT (not the old bare digit regex) accepts a
        // leading "+" — a valid integer representation, deliberately pinned
        // so a future change to the coercion strategy doesn't silently
        // narrow this back down unnoticed.
        $container = $this->createStub(ContainerInterface::class);

        $controller = new class {
            public function action(int $id): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $resolver = new ControllerArgumentResolver($container, new ReflectionService());
        $args = $resolver->resolve($controller, 'action', $request, ['id' => '+5']);

        static::assertSame([5], $args);
    }

    public function testRejectsLeadingZeroForBuiltinInt(): void
    {
        // "05" and "5" would otherwise alias to the same resource ID under
        // two different URL strings — FILTER_VALIDATE_INT rejects it.
        $container = $this->createStub(ContainerInterface::class);

        $controller = new class {
            public function show(int $id): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $resolver = new ControllerArgumentResolver($container, new ReflectionService());

        $this->expectException(ValidationException::class);

        $resolver->resolve($controller, 'show', $request, ['id' => '05']);
    }

    public function testRejectsOutOfRangeDigitStringForBuiltinInt(): void
    {
        // A digit string longer than PHP_INT_MAX must be rejected outright,
        // not silently aliased to PHP_INT_MAX by an unbounded (int) cast —
        // the same "reject, don't silently normalize" defect class the
        // original 'abc' -> 0 bug was, just harder to trigger.
        $container = $this->createStub(ContainerInterface::class);

        $controller = new class {
            public function show(int $id): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $resolver = new ControllerArgumentResolver($container, new ReflectionService());

        try {
            $resolver->resolve($controller, 'show', $request, ['id' => '99999999999999999999']);
            static::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            static::assertSame('id', $e->getField());
            static::assertSame(422, $e->getCode());
        }
    }

    public function testRejectsNonNumericRouteParameterForBuiltinInt(): void
    {
        // Regression: `orders/{id}` bound to `show(int $id)` — an invalid
        // segment like "abc" must be rejected with a 422-equivalent, not
        // silently normalized to 0 by a bare `(int) 'abc'` cast.
        $container = $this->createStub(ContainerInterface::class);

        $controller = new class {
            public function show(int $id): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $resolver = new ControllerArgumentResolver($container, new ReflectionService());

        try {
            $resolver->resolve($controller, 'show', $request, ['id' => 'abc']);
            static::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            static::assertSame('id', $e->getField());
            static::assertSame(422, $e->getCode());
            static::assertStringContainsString('must be of type int', $e->getMessage());
        }
    }

    public function testRejectsFloatLookingRouteParameterForBuiltinInt(): void
    {
        // "12.5" is numeric but not a valid int literal — must still be rejected
        // rather than silently truncated.
        $container = $this->createStub(ContainerInterface::class);

        $controller = new class {
            public function show(int $id): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $resolver = new ControllerArgumentResolver($container, new ReflectionService());

        $this->expectException(ValidationException::class);

        $resolver->resolve($controller, 'show', $request, ['id' => '12.5']);
    }

    public function testCastsRouteParameterToBuiltinFloat(): void
    {
        $container = $this->createStub(ContainerInterface::class);

        $controller = new class {
            public function action(float $ratio): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $resolver = new ControllerArgumentResolver($container, new ReflectionService());
        $args = $resolver->resolve($controller, 'action', $request, ['ratio' => '1.5']);

        static::assertSame([1.5], $args);
    }

    public function testRejectsNonNumericRouteParameterForBuiltinFloat(): void
    {
        $container = $this->createStub(ContainerInterface::class);

        $controller = new class {
            public function action(float $ratio): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $resolver = new ControllerArgumentResolver($container, new ReflectionService());

        try {
            $resolver->resolve($controller, 'action', $request, ['ratio' => 'not-a-number']);
            static::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            static::assertSame('ratio', $e->getField());
            static::assertSame(422, $e->getCode());
            static::assertStringContainsString('must be of type float', $e->getMessage());
        }
    }

    public function testRejectsInfinityOverflowingRouteParameterForBuiltinFloat(): void
    {
        // "1e400" is_numeric()-true and casts cleanly to INF — must be
        // rejected, not silently accepted as a "valid" float.
        $container = $this->createStub(ContainerInterface::class);

        $controller = new class {
            public function action(float $ratio): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $resolver = new ControllerArgumentResolver($container, new ReflectionService());

        try {
            $resolver->resolve($controller, 'action', $request, ['ratio' => '1e400']);
            static::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            static::assertSame('ratio', $e->getField());
            static::assertSame(422, $e->getCode());
        }
    }

    public function testCastsRouteParameterToBuiltinBool(): void
    {
        $container = $this->createStub(ContainerInterface::class);

        $controller = new class {
            public function action(bool $active): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $resolver = new ControllerArgumentResolver($container, new ReflectionService());
        $args = $resolver->resolve($controller, 'action', $request, ['active' => 'true']);

        static::assertSame([true], $args);
    }

    public function testAcceptsAlreadyTypedIntRouteParameter(): void
    {
        // Defensive path: the interface's $routeParams is array<string, mixed> —
        // a caller that already resolved a typed value (not from raw URL
        // parsing) should pass through without re-parsing.
        $container = $this->createStub(ContainerInterface::class);

        $controller = new class {
            public function action(int $id): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $resolver = new ControllerArgumentResolver($container, new ReflectionService());
        $args = $resolver->resolve($controller, 'action', $request, ['id' => 42]);

        static::assertSame([42], $args);
    }

    public function testAcceptsAlreadyTypedFloatRouteParameter(): void
    {
        $container = $this->createStub(ContainerInterface::class);

        $controller = new class {
            public function action(float $ratio): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $resolver = new ControllerArgumentResolver($container, new ReflectionService());
        $args = $resolver->resolve($controller, 'action', $request, ['ratio' => 1.5]);

        static::assertSame([1.5], $args);
    }

    public function testAcceptsAlreadyTypedBoolRouteParameter(): void
    {
        $container = $this->createStub(ContainerInterface::class);

        $controller = new class {
            public function action(bool $active): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $resolver = new ControllerArgumentResolver($container, new ReflectionService());
        $args = $resolver->resolve($controller, 'action', $request, ['active' => true]);

        static::assertSame([true], $args);
    }

    public function testRejectsUnrecognizedRouteParameterForBuiltinBool(): void
    {
        // Unlike a bare filter_var(..., FILTER_VALIDATE_BOOLEAN) — which would
        // silently coerce "maybe" to false — an unrecognized value must be
        // rejected via the explicit allow-list.
        $container = $this->createStub(ContainerInterface::class);

        $controller = new class {
            public function action(bool $active): void {}
        };

        $request = $this->createStub(ServerRequestInterface::class);

        $resolver = new ControllerArgumentResolver($container, new ReflectionService());

        try {
            $resolver->resolve($controller, 'action', $request, ['active' => 'maybe']);
            static::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            static::assertSame('active', $e->getField());
            static::assertSame(422, $e->getCode());
            static::assertStringContainsString('must be of type bool', $e->getMessage());
        }
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
