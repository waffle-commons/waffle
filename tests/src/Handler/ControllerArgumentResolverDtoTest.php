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
use WaffleTests\Helper\Dto\OptionalFieldsDto;
use WaffleTests\Helper\Dto\PlainRejectionDto;
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

    public function testConstructorTypeErrorIsTranslatedToValidationException(): void
    {
        $controller = new class {
            public function register(UserRegistrationDto $payload): void {}
        };

        $resolver = new ControllerArgumentResolver($this->container(), new ReflectionService());

        try {
            // UserRegistrationDto::__construct expects int $age; passing a string
            // bypasses the Property Hook and trips PHP's native TypeError at
            // constructor invocation — the resolver must translate it.
            $resolver->resolve(
                controller: $controller,
                method: 'register',
                request: $this->request(['email' => 'alice@example.com', 'age' => 'thirty']),
                routeParams: [],
            );
            static::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            static::assertSame(422, $e->getCode());
            static::assertNull($e->getField());
            static::assertInstanceOf(\TypeError::class, $e->getPrevious());
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
}
