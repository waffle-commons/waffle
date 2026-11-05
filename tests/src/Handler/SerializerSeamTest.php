<?php

declare(strict_types=1);

namespace WaffleTests\Handler;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Contracts\Serializer\DtoSerializerInterface;
use Waffle\Handler\ControllerArgumentResolver;
use Waffle\Handler\ControllerDispatcher;
use Waffle\Handler\ControllerResponseConverter;
use Waffle\Service\ReflectionService;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\Dto\UserRegistrationDto;
use WaffleTests\Helper\RecordingSerializer;

/**
 * API-02: with a serializer registered, the framework hydrates `#[Dto]`
 * parameters and writes returned `#[Dto]` objects through it; without one,
 * nothing changes.
 */
#[CoversClass(ControllerArgumentResolver::class)]
#[CoversClass(ControllerResponseConverter::class)]
#[CoversClass(ControllerDispatcher::class)]
#[AllowMockObjectsWithoutExpectations]
final class SerializerSeamTest extends TestCase
{
    public function testTheSerializerHydratesADtoFromTheParsedBody(): void
    {
        $dto = new UserRegistrationDto('ada@example.com', 36);
        $serializer = new RecordingSerializer($dto);
        $body = ['email' => 'ada@example.com', 'age' => 36];

        $args = $this->resolver($serializer)->resolve(
            controller: new RegistrationController(),
            method: 'register',
            request: $this->request(parsedBody: $body),
            routeParams: [],
        );

        static::assertSame([$dto], $args);
        static::assertSame([[$body, UserRegistrationDto::class]], $serializer->denormalized);
        static::assertSame([], $serializer->deserialized);
    }

    public function testWithoutAParsedBodyTheSerializerDecodesTheRawBody(): void
    {
        $dto = new UserRegistrationDto('ada@example.com', 36);
        $serializer = new RecordingSerializer($dto);
        $json = '{"email":"ada@example.com","age":36}';

        $args = $this->resolver($serializer)->resolve(
            controller: new RegistrationController(),
            method: 'register',
            request: $this->request(parsedBody: null, rawBody: $json),
            routeParams: [],
        );

        static::assertSame([$dto], $args);
        static::assertSame([[$json, UserRegistrationDto::class]], $serializer->deserialized);
    }

    public function testADtoTheSerializerDoesNotHandleIsHydratedAsBefore(): void
    {
        $serializer = new RecordingSerializer(handles: false);

        $args = $this->resolver($serializer)->resolve(
            controller: new RegistrationController(),
            method: 'register',
            request: $this->request(parsedBody: ['email' => 'Ada@Example.com', 'age' => 36]),
            routeParams: [],
        );

        static::assertInstanceOf(UserRegistrationDto::class, $args[0]);
        static::assertSame('ada@example.com', $args[0]->email);
        static::assertSame([], $serializer->denormalized);
    }

    public function testTheConverterWritesAReturnedDtoWithTheSerializer(): void
    {
        $dto = new UserRegistrationDto('ada@example.com', 36);
        $serializer = new RecordingSerializer();
        $body = $this->createMock(StreamInterface::class);
        $body->expects($this->once())->method('write')->with(RecordingSerializer::JSON);
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->method('getBody')->willReturn($body);
        $factory = $this->createMock(ResponseFactoryInterface::class);
        $factory->expects($this->once())->method('createResponse')->with(200)->willReturn($response);

        $result = new ControllerResponseConverter($factory, serializer: $serializer)->convert($dto);

        static::assertSame($response, $result);
        static::assertSame([$dto], $serializer->serialized);
    }

    public function testTheConverterLeavesWhatTheSerializerDoesNotHandle(): void
    {
        $serializer = new RecordingSerializer(handles: false);
        $factory = $this->createMock(ResponseFactoryInterface::class);
        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->with(204)
            ->willReturn($this->createStub(ResponseInterface::class));

        new ControllerResponseConverter($factory, serializer: $serializer)->convert(null);

        static::assertSame([], $serializer->serialized);
    }

    public function testTheDispatcherHandsItsSerializerToTheDefaultConverter(): void
    {
        $dto = new UserRegistrationDto('ada@example.com', 36);
        $serializer = new RecordingSerializer();
        $controller = new class($dto) {
            public function __construct(
                private readonly UserRegistrationDto $dto,
            ) {}

            public function show(): UserRegistrationDto
            {
                return $this->dto;
            }
        };
        $body = $this->createStub(StreamInterface::class);
        $response = $this->createStub(ResponseInterface::class);
        $response->method('withHeader')->willReturnSelf();
        $response->method('getBody')->willReturn($body);
        $factory = $this->createStub(ResponseFactoryInterface::class);
        $factory->method('createResponse')->willReturn($response);
        $services = [
            $controller::class => $controller,
            ResponseFactoryInterface::class => $factory,
        ];
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn(string $id): bool => array_key_exists($id, $services));
        $container->method('get')->willReturnCallback(static fn(string $id): ?object => $services[$id] ?? null);
        $request = $this->createStub(ServerRequestInterface::class);
        $request
            ->method('getAttribute')
            ->willReturnMap([
                ['_classname', null, $controller::class],
                ['_method', null, 'show'],
                ['_params', [], []],
            ]);

        $result = new ControllerDispatcher(
            $container,
            null,
            new ControllerArgumentResolver($container, new ReflectionService()),
            serializer: $serializer,
        )->handle($request);

        static::assertSame($response, $result);
        static::assertSame([$dto], $serializer->serialized);
    }

    private function resolver(DtoSerializerInterface $serializer): ControllerArgumentResolver
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        return new ControllerArgumentResolver($container, new ReflectionService(), $serializer);
    }

    private function request(?array $parsedBody, string $rawBody = ''): ServerRequestInterface
    {
        $body = $this->createStub(StreamInterface::class);
        $body->method('__toString')->willReturn($rawBody);
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($parsedBody);
        $request->method('getBody')->willReturn($body);

        return $request;
    }
}

/**
 * A controller whose action takes a `#[Dto]`.
 */
final class RegistrationController
{
    public function register(UserRegistrationDto $payload): UserRegistrationDto
    {
        return $payload;
    }
}
