<?php

declare(strict_types=1);

namespace WaffleTests\Handler;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Exception\ValidationException;
use Waffle\Handler\ControllerArgumentResolver;
use Waffle\Service\ReflectionService;
use WaffleTests\Helper\Dto\EmptyDto;
use WaffleTests\Helper\Dto\IntersectionFieldDto;
use WaffleTests\Helper\Dto\NullableNoDefaultDto;
use WaffleTests\Helper\Dto\ObjectFieldDto;
use WaffleTests\Helper\Dto\OptionalFieldsDto;
use WaffleTests\Helper\Dto\PlainRejectionDto;
use WaffleTests\Helper\Dto\TypedFieldsDto;
use WaffleTests\Helper\Dto\UnionFieldDto;
use WaffleTests\Helper\Dto\UserRegistrationDto;

#[CoversClass(ControllerArgumentResolver::class)]
#[AllowMockObjectsWithoutExpectations]
final class ControllerArgumentResolverDtoTest extends TestCase
{
    private function request(mixed $parsedBody): ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($parsedBody);
        return $request;
    }

    private function container(): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        // DTOs must NOT be served from the container — the resolver should win on the #[Dto] check.
        $container->method('has')->willReturn(false);
        return $container;
    }

    public function testHydratesDtoFromArrayBody(): void
    {
        $controller = new class {
            public ?UserRegistrationDto $captured = null;

            public function register(UserRegistrationDto $payload): void
            {
                $this->captured = $payload;
            }
        };

        $resolver = new ControllerArgumentResolver($this->container(), new ReflectionService());

        $args = $resolver->resolve(
            controller: $controller,
            method: 'register',
            request: $this->request(['email' => 'Alice@Example.com', 'age' => 30]),
            routeParams: [],
        );

        static::assertCount(1, $args);
        $dto = $args[0];
        static::assertInstanceOf(UserRegistrationDto::class, $dto);
        static::assertSame('alice@example.com', $dto->email);
        static::assertSame(30, $dto->age);
    }

    public function testRejectsNonArrayBody(): void
    {
        $controller = new class {
            public function register(UserRegistrationDto $payload): void {}
        };

        $resolver = new ControllerArgumentResolver($this->container(), new ReflectionService());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Expected JSON object body');

        $resolver->resolve(controller: $controller, method: 'register', request: $this->request(null), routeParams: []);
    }

    public function testPropertyHookRejectionBubblesAsValidationException(): void
    {
        $controller = new class {
            public function register(UserRegistrationDto $payload): void {}
        };

        $resolver = new ControllerArgumentResolver($this->container(), new ReflectionService());

        try {
            $resolver->resolve(
                controller: $controller,
                method: 'register',
                request: $this->request(['email' => 'not-an-email', 'age' => 30]),
                routeParams: [],
            );
            static::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            static::assertSame('email', $e->getField());
            static::assertStringContainsString('Invalid email format', $e->getMessage());
        }
    }

    public function testMissingRequiredFieldThrowsBeforeConstruction(): void
    {
        $controller = new class {
            public function register(UserRegistrationDto $payload): void {}
        };

        $resolver = new ControllerArgumentResolver($this->container(), new ReflectionService());

        try {
            $resolver->resolve(
                controller: $controller,
                method: 'register',
                request: $this->request(['email' => 'alice@example.com']), // 'age' missing
                routeParams: [],
            );
            static::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            static::assertSame('age', $e->getField());
            static::assertStringContainsString('Missing required field', $e->getMessage());
        }
    }

    public function testUnknownKeysInBodyAreIgnored(): void
    {
        $controller = new class {
            public ?UserRegistrationDto $captured = null;

            public function register(UserRegistrationDto $payload): void
            {
                $this->captured = $payload;
            }
        };

        $resolver = new ControllerArgumentResolver($this->container(), new ReflectionService());

        // 'admin' is not in the DTO constructor and must be silently dropped.
        $args = $resolver->resolve(
            controller: $controller,
            method: 'register',
            request: $this->request(['email' => 'alice@example.com', 'age' => 30, 'admin' => true]),
            routeParams: [],
        );

        $dto = $args[0];
        static::assertInstanceOf(UserRegistrationDto::class, $dto);
        static::assertSame('alice@example.com', $dto->email);
    }

    public function testEmptyDtoIsInstantiatedDirectly(): void
    {
        $controller = new class {
            public ?EmptyDto $captured = null;

            public function ping(EmptyDto $payload): void
            {
                $this->captured = $payload;
            }
        };

        $resolver = new ControllerArgumentResolver($this->container(), new ReflectionService());

        $args = $resolver->resolve(
            controller: $controller,
            method: 'ping',
            request: $this->request([]),
            routeParams: [],
        );

        static::assertInstanceOf(EmptyDto::class, $args[0]);
    }

    public function testPlainInvalidArgumentExceptionIsTranslatedToValidationException(): void
    {
        $controller = new class {
            public function consume(PlainRejectionDto $payload): void {}
        };

        $resolver = new ControllerArgumentResolver($this->container(), new ReflectionService());

        try {
            $resolver->resolve(
                controller: $controller,
                method: 'consume',
                request: $this->request(['code' => '']),
                routeParams: [],
            );
            static::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            // Plain \InvalidArgumentException from a Property Hook → unified 422
            // with no field name (DTO author opted out of structured reporting).
            static::assertNull($e->getField());
            static::assertSame(422, $e->getCode());
            static::assertSame('code must not be empty.', $e->getMessage());
            static::assertInstanceOf(\InvalidArgumentException::class, $e->getPrevious());
        }
    }

    public function testScalarTypeMismatchIsRejectedAsFieldLevelValidationError(): void
    {
        $controller = new class {
            public function register(UserRegistrationDto $payload): void {}
        };

        $resolver = new ControllerArgumentResolver($this->container(), new ReflectionService());

        try {
            // UserRegistrationDto::__construct expects int $age. A string body value
            // is rejected up-front as a typed, field-level 422 — the resolver no longer
            // relies on catching PHP's native TypeError (an Error subclass).
            $resolver->resolve(
                controller: $controller,
                method: 'register',
                request: $this->request(['email' => 'alice@example.com', 'age' => 'thirty']),
                routeParams: [],
            );
            static::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            static::assertSame(422, $e->getCode());
            static::assertSame('age', $e->getField());
            static::assertStringContainsString('must be of type', $e->getMessage());
            static::assertNull($e->getPrevious());
        }
    }

    public function testOptionalFieldsAreOmittedWhenMissing(): void
    {
        $controller = new class {
            public function consume(OptionalFieldsDto $payload): void {}
        };

        $resolver = new ControllerArgumentResolver($this->container(), new ReflectionService());

        // Provide only 'favoriteNumber'; 'nickname' has a default and should not be required.
        $args = $resolver->resolve(
            controller: $controller,
            method: 'consume',
            request: $this->request(['favoriteNumber' => 7]),
            routeParams: [],
        );

        $dto = $args[0];
        static::assertInstanceOf(OptionalFieldsDto::class, $dto);
        static::assertSame('anon', $dto->nickname);
        static::assertSame(7, $dto->favoriteNumber);
    }

    public function testNullableScalarAcceptsExplicitNull(): void
    {
        $controller = new class {
            public function consume(OptionalFieldsDto $payload): void {}
        };

        $resolver = new ControllerArgumentResolver($this->container(), new ReflectionService());

        $args = $resolver->resolve(
            controller: $controller,
            method: 'consume',
            request: $this->request(['favoriteNumber' => null]),
            routeParams: [],
        );

        $dto = $args[0];
        static::assertInstanceOf(OptionalFieldsDto::class, $dto);
        static::assertNull($dto->favoriteNumber);
    }

    public function testNonNullableScalarRejectsExplicitNull(): void
    {
        $controller = new class {
            public function register(UserRegistrationDto $payload): void {}
        };

        $resolver = new ControllerArgumentResolver($this->container(), new ReflectionService());

        try {
            $resolver->resolve(
                controller: $controller,
                method: 'register',
                request: $this->request(['email' => 'alice@example.com', 'age' => null]),
                routeParams: [],
            );
            static::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            static::assertSame('age', $e->getField());
            static::assertSame(422, $e->getCode());
        }
    }

    public function testNullableParameterWithoutDefaultHydratesToNull(): void
    {
        $controller = new class {
            public function consume(NullableNoDefaultDto $payload): void {}
        };

        $resolver = new ControllerArgumentResolver($this->container(), new ReflectionService());

        // 'note' is absent, has no default, but is nullable → resolver fills null.
        $args = $resolver->resolve(
            controller: $controller,
            method: 'consume',
            request: $this->request([]),
            routeParams: [],
        );

        $dto = $args[0];
        static::assertInstanceOf(NullableNoDefaultDto::class, $dto);
        static::assertNull($dto->note);
    }

    public function testUnionTypedFieldAcceptsAnyMemberType(): void
    {
        $controller = new class {
            public function consume(UnionFieldDto $payload): void {}
        };

        $resolver = new ControllerArgumentResolver($this->container(), new ReflectionService());

        $args = $resolver->resolve(
            controller: $controller,
            method: 'consume',
            request: $this->request(['value' => 'a-string']),
            routeParams: [],
        );

        static::assertInstanceOf(UnionFieldDto::class, $args[0]);
        static::assertSame('a-string', $args[0]->value);
    }

    public function testUnionTypedFieldRejectsValueOutsideTheUnion(): void
    {
        $controller = new class {
            public function consume(UnionFieldDto $payload): void {}
        };

        $resolver = new ControllerArgumentResolver($this->container(), new ReflectionService());

        try {
            // 1.5 is neither int nor string — outside the int|string union.
            $resolver->resolve(
                controller: $controller,
                method: 'consume',
                request: $this->request(['value' => 1.5]),
                routeParams: [],
            );
            static::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            static::assertSame('value', $e->getField());
            static::assertSame(422, $e->getCode());
        }
    }

    public function testFloatBoolArrayAndMixedFieldsAreAccepted(): void
    {
        $controller = new class {
            public function consume(TypedFieldsDto $payload): void {}
        };

        $resolver = new ControllerArgumentResolver($this->container(), new ReflectionService());

        $args = $resolver->resolve(
            controller: $controller,
            method: 'consume',
            request: $this->request([
                'ratio' => 1.5,
                'active' => true,
                'tags' => ['a', 'b'],
                'extra' => 'anything',
            ]),
            routeParams: [],
        );

        $dto = $args[0];
        static::assertInstanceOf(TypedFieldsDto::class, $dto);
        static::assertSame(1.5, $dto->ratio);
        static::assertTrue($dto->active);
        static::assertSame(['a', 'b'], $dto->tags);
    }

    public function testObjectTypedFieldRejectsScalarBodyValue(): void
    {
        $controller = new class {
            public function consume(ObjectFieldDto $payload): void {}
        };

        $resolver = new ControllerArgumentResolver($this->container(), new ReflectionService());

        try {
            $resolver->resolve(
                controller: $controller,
                method: 'consume',
                request: $this->request(['obj' => 'not-an-object']),
                routeParams: [],
            );
            static::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            static::assertSame('obj', $e->getField());
            static::assertSame(422, $e->getCode());
        }
    }

    public function testIntersectionTypedFieldDefersToConstruction(): void
    {
        $controller = new class {
            public function consume(IntersectionFieldDto $payload): void {}
        };

        $resolver = new ControllerArgumentResolver($this->container(), new ReflectionService());

        // Intersection types are neither named nor union — the resolver defers the
        // type check to the constructor. An ArrayObject satisfies Countable&ArrayAccess.
        $args = $resolver->resolve(
            controller: $controller,
            method: 'consume',
            request: $this->request(['bag' => new \ArrayObject([1, 2, 3])]),
            routeParams: [],
        );

        static::assertInstanceOf(IntersectionFieldDto::class, $args[0]);
        static::assertCount(3, $args[0]->bag);
    }
}
