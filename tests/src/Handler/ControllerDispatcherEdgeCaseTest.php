<?php

declare(strict_types=1);

namespace WaffleTests\Handler;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Waffle\Abstract\AbstractController;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Handler\ControllerDispatcher;
use WaffleTests\Abstract\Helper\StubServerRequest;

#[CoversClass(ControllerDispatcher::class)]
#[AllowMockObjectsWithoutExpectations]
class ControllerDispatcherEdgeCaseTest extends TestCase
{
    public function testHandleReturnsResponseInterfaceDirectly(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $controller = new class($responseMock) {
            public function __construct(
                private ResponseInterface $response,
            ) {}

            public function index(): ResponseInterface
            {
                return $this->response;
            }
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($controller);

        $dispatcher = new ControllerDispatcher($container);

        $request = new StubServerRequest('GET', '/');
        $request = $request->withAttribute('_classname', 'TestController')->withAttribute('_method', 'index');

        $result = $dispatcher->handle($request);

        static::assertSame($responseMock, $result);
    }

    public function testHandleConvertsStringResponse(): void
    {
        $controller = new class {
            public function index(): string
            {
                return 'Hello World';
            }
        };

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->expects($this->once())->method('write')->with('Hello World');
        $responseMock->method('getBody')->willReturn($streamMock);
        $responseMock->method('withHeader')->willReturnSelf();

        $factoryMock = $this->createMock(ResponseFactoryInterface::class);
        $factoryMock->method('createResponse')->willReturn($responseMock);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->willReturnMap([
                ['TestController',                true],
                [ResponseFactoryInterface::class, true],
            ]);
        $container
            ->method('get')
            ->willReturnMap([
                ['TestController',                $controller],
                [ResponseFactoryInterface::class, $factoryMock],
            ]);

        $dispatcher = new ControllerDispatcher($container);

        $request = new StubServerRequest('GET', '/');
        $request = $request->withAttribute('_classname', 'TestController')->withAttribute('_method', 'index');

        $dispatcher->handle($request);
    }

    public function testHandleInjectsRequestInterface(): void
    {
        $controller = new class {
            public null|ServerRequestInterface $capturedRequest = null;

            public function index(ServerRequestInterface $request): null|array
            {
                $this->capturedRequest = $request;
                return null;
            }
        };

        $factoryMock = $this->createMock(ResponseFactoryInterface::class);
        $factoryMock->method('createResponse')->willReturn($this->createMock(ResponseInterface::class));

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container
            ->method('get')
            ->willReturnMap([
                ['TestController',                $controller],
                [ResponseFactoryInterface::class, $factoryMock],
            ]);

        $dispatcher = new ControllerDispatcher($container);

        $request = new StubServerRequest('GET', '/');
        $request = $request->withAttribute('_classname', 'TestController')->withAttribute('_method', 'index');

        $dispatcher->handle($request);

        static::assertSame($request, $controller->capturedRequest);
    }

    public function testHandleInjectsServiceFromContainer(): void
    {
        $service = new \stdClass();
        $controller = new class {
            public null|\stdClass $capturedService = null;

            public function index(\stdClass $service): null|array
            {
                $this->capturedService = $service;
                return null;
            }
        };

        $factoryMock = $this->createMock(ResponseFactoryInterface::class);
        $factoryMock->method('createResponse')->willReturn($this->createMock(ResponseInterface::class));

        $container = $this->createMock(ContainerInterface::class);
        // has() checks for Service class
        $container
            ->method('has')
            ->willReturnCallback(
                fn($id) => $id === 'stdClass' || $id === 'TestController' || $id === ResponseFactoryInterface::class,
            );
        $container
            ->method('get')
            ->willReturnMap([
                ['TestController',                $controller],
                ['stdClass',                      $service],
                [ResponseFactoryInterface::class, $factoryMock],
            ]);

        $dispatcher = new ControllerDispatcher($container);

        $request = new StubServerRequest('GET', '/');
        $request = $request->withAttribute('_classname', 'TestController')->withAttribute('_method', 'index');

        $dispatcher->handle($request);

        static::assertSame($service, $controller->capturedService);
    }

    public function testHandleInjectsResponseFactoryIntoAwareController(): void
    {
        $controller = new class extends AbstractController {
            public function index(): null|array
            {
                return null;
            }

            public function getFactory(): null|ResponseFactoryInterface
            {
                return $this->responseFactory ?? null;
            }
        };

        $factoryMock = $this->createMock(ResponseFactoryInterface::class);
        $factoryMock->method('createResponse')->willReturn($this->createMock(ResponseInterface::class));

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container
            ->method('get')
            ->willReturnMap([
                ['TestController',                $controller],
                [ResponseFactoryInterface::class, $factoryMock],
            ]);

        $dispatcher = new ControllerDispatcher($container);

        $request = new StubServerRequest('GET', '/');
        $request = $request->withAttribute('_classname', 'TestController')->withAttribute('_method', 'index');

        $dispatcher->handle($request);

        static::assertSame($factoryMock, $controller->getFactory());
    }
}
