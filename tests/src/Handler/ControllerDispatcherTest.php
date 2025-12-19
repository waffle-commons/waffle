<?php

declare(strict_types=1);

namespace WaffleTests\Handler;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Waffle\Abstract\AbstractController;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Handler\ControllerDispatcher;
use WaffleTests\Abstract\StubResponse;

#[AllowMockObjectsWithoutExpectations]
class ControllerDispatcherTest extends TestCase
{
    private ContainerInterface&MockObject $container;
    private ServerRequestInterface $request;
    private ControllerDispatcher $dispatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->request = $this->createStub(ServerRequestInterface::class);
        $this->dispatcher = new ControllerDispatcher($this->container);
    }

    public function testHandleThrowsExceptionIfAttributesMissing(): void
    {
        $this->request
            ->method('getAttribute')
            ->willReturnMap([
                ['_classname', null, null],
                ['_method',    null, null],
            ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Pipeline Error: No controller defined');

        $this->dispatcher->handle($this->request);
    }

    public function testHandleThrowsExceptionIfAttributesInvalid(): void
    {
        $this->request
            ->method('getAttribute')
            ->willReturnMap([
                ['_classname', null, 123], // Invalid type
                ['_method',    null, 'index'],
            ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Pipeline Error: Invalid controller attributes');

        $this->dispatcher->handle($this->request);
    }

    public function testHandleLazyLoadsController(): void
    {
        $className = 'MyController';
        $this->request
            ->method('getAttribute')
            ->willReturnMap([
                ['_classname', null, $className],
                ['_method', null, 'index'],
                ['_params', [], []], // Fix: Provide params default
            ]);

        /** @var ContainerInterface&MockObject $container */
        $this->container = $this->createMock(ContainerInterface::class); // Keep as mock for checks
        $this->container
            ->expects($this->once())
            ->method('has')
            ->with($className)
            ->willReturn(false);
        $this->container
            ->expects($this->once())
            ->method('set')
            ->with($className, $className);
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with($className)
            ->willReturn(new class {
                public function index()
                {
                    return new StubResponse(200);
                }
            });

        $this->dispatcher = new ControllerDispatcher($this->container); // Re-init with mock
        $this->dispatcher->handle($this->request);
    }

    public function testHandleThrowsExceptionIfMethodNotFound(): void
    {
        $className = 'MyController';
        $this->request
            ->method('getAttribute')
            ->willReturnMap([
                ['_classname', null, $className],
                ['_method',    null, 'missingMethod'],
            ]);

        $controller = new class {};
        $this->container->method('has')->willReturn(true);
        $this->container->method('get')->willReturn($controller);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Dispatcher Error: Method "missingMethod" not found');

        $this->dispatcher->handle($this->request);
    }

    public function testHandleWarnsButDoesNotCrashIfResponseFactoryMissing(): void
    {
        $controller = new class extends AbstractController {
            public function index()
            {
                return new StubResponse(200);
            }
        };
        $className = get_class($controller);

        $this->request
            ->method('getAttribute')
            ->willReturnMap([
                ['_classname', null, $className],
                ['_method', null, 'index'],
                ['_params', [], []],
            ]);

        $this->container
            ->method('has')
            ->willReturnMap([
                [$className,                      true],
                [ResponseFactoryInterface::class, false],
            ]);

        $this->container
            ->method('get')
            ->with($className)
            ->willReturn($controller);

        $response = $this->dispatcher->handle($this->request);
        static::assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testResolveArgumentsAutoCastsBuiltins(): void
    {
        $controller = new class {
            public $args = [];

            public function action(int $id, bool $flag, float $amount, string $slug)
            {
                $this->args = [$id, $flag, $amount, $slug];
                return new StubResponse(200);
            }
        };

        $this->request
            ->method('getAttribute')
            ->willReturnMap([
                ['_classname', null, 'C'],
                ['_method', null, 'action'],
                ['_params', [], ['id' => '123', 'flag' => 'true', 'amount' => '10.5', 'slug' => 'foo']],
            ]);

        $this->container->method('get')->willReturn($controller);

        $this->dispatcher->handle($this->request);

        static::assertSame(123, $controller->args[0]);
        static::assertTrue($controller->args[1]);
        static::assertSame(10.5, $controller->args[2]);
        static::assertSame('foo', $controller->args[3]);
    }

    public function testResolveArgumentsHandlesNullable(): void
    {
        $controller = new class {
            public $calledWith = 'not-null';

            public function action(null|string $optional)
            {
                $this->calledWith = $optional;
                return new StubResponse(200);
            }
        };

        $this->request
            ->method('getAttribute')
            ->willReturnMap([
                ['_classname', null, 'C'],
                ['_method', null, 'action'],
                ['_params', [], []],
            ]);

        $this->container->method('get')->willReturn($controller);

        $this->dispatcher->handle($this->request);

        static::assertNull($controller->calledWith);
    }

    public function testResolveArgumentsMaintainsDefaultValues(): void
    {
        $controller = new class {
            public $val;

            public function action($d = 'default')
            {
                $this->val = $d;
                return new StubResponse(200);
            }
        };

        $this->request
            ->method('getAttribute')
            ->willReturnMap([
                ['_classname', null, 'C'],
                ['_method', null, 'action'],
                ['_params', [], []],
            ]);

        $this->container->method('get')->willReturn($controller);
        $this->dispatcher->handle($this->request);

        static::assertSame('default', $controller->val);
    }

    public function testResolveArgumentsThrowsExceptionForUnresolvable(): void
    {
        $controller = new class {
            public function action(\stdClass $_required)
            {
                return new StubResponse(200);
            }
        };

        $this->request
            ->method('getAttribute')
            ->willReturnMap([
                ['_classname', null, 'C'],
                ['_method', null, 'action'],
                ['_params', [], []],
            ]);

        $this->container->method('get')->willReturn($controller);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be resolved');

        $this->dispatcher->handle($this->request);
    }

    public function testHandlesResponseConversionError(): void
    {
        $controller = new class {
            public function action()
            {
                return 123;
            } // Int return not supported
        };

        $this->request
            ->method('getAttribute')
            ->willReturnMap([
                ['_classname', null, 'C'],
                ['_method', null, 'action'],
                ['_params', [], []],
            ]);

        // Redefine container behavior accurately
        $c = $this->createStub(ContainerInterface::class);
        $c->method('has')->willReturnMap([
            ['C',                             true],
            [ResponseFactoryInterface::class, true],
        ]);
        $c->method('get')->willReturnMap([
            ['C', $controller],
            [ResponseFactoryInterface::class, $this->createStub(ResponseFactoryInterface::class)],
        ]);

        $d = new ControllerDispatcher($c);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no conversion strategy matched');

        $d->handle($this->request);
    }

    public function testHandleConvertsViewResponse(): void
    {
        $view = new class implements \Waffle\Commons\Contracts\View\ViewInterface {
            public $data = ['foo' => 'bar'];

            public function render(): string
            {
                return '';
            }
        };

        $controller = new class($view) {
            public function __construct(
                private $view,
            ) {}

            public function action()
            {
                return $this->view;
            }
        };

        $this->request
            ->method('getAttribute')
            ->willReturnMap([
                ['_classname', null, 'C'],
                ['_method', null, 'action'],
                ['_params', [], []],
            ]);

        // Setup factory
        /** @var ResponseFactoryInterface&MockObject $factory */
        $factory = $this->createMock(ResponseFactoryInterface::class); // Expectations used
        /** @var ResponseInterface&MockObject $response */
        $response = $this->createMock(ResponseInterface::class); // Expectations used
        /** @var StreamInterface&MockObject $stream */
        $stream = $this->createMock(StreamInterface::class); // Expectations used

        $response->method('getBody')->willReturn($stream);
        $response
            ->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $factory->method('createResponse')->with(200)->willReturn($response);

        // Setup stream write expectation
        $stream->expects($this->once())->method('write')->with('{"foo":"bar"}');

        /** @var ContainerInterface&MockObject $c */
        $c = $this->createMock(ContainerInterface::class);
        $c->method('has')->willReturnMap([
            ['C',                             true],
            [ResponseFactoryInterface::class, true],
        ]);
        $c->method('get')->willReturnMap([
            ['C',                             $controller],
            [ResponseFactoryInterface::class, $factory],
        ]);

        $d = new ControllerDispatcher($c);
        $d->handle($this->request);
    }

    public function testHandleConvertsStringableResponse(): void
    {
        $obj = new class {
            public function __toString()
            {
                return 'Stringable Content';
            }
        };

        $controller = new class($obj) {
            public function __construct(
                private $obj,
            ) {}

            public function action()
            {
                return $this->obj;
            }
        };

        $this->request
            ->method('getAttribute')
            ->willReturnMap([
                ['_classname', null, 'C'],
                ['_method', null, 'action'],
                ['_params', [], []],
            ]);

        /** @var ResponseFactoryInterface&MockObject $factory */
        $factory = $this->createMock(ResponseFactoryInterface::class);
        /** @var ResponseInterface&MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        /** @var StreamInterface&MockObject $stream */
        $stream = $this->createMock(StreamInterface::class);

        $response->method('getBody')->willReturn($stream);
        $factory->method('createResponse')->with(200)->willReturn($response);

        $stream->expects($this->once())->method('write')->with('Stringable Content');

        /** @var ContainerInterface&MockObject $c */
        $c = $this->createMock(ContainerInterface::class);
        $c->method('has')->willReturnMap([
            ['C',                             true],
            [ResponseFactoryInterface::class, true],
        ]);
        $c->method('get')->willReturnMap([
            ['C',                             $controller],
            [ResponseFactoryInterface::class, $factory],
        ]);

        $d = new ControllerDispatcher($c);
        $d->handle($this->request);
    }

    public function testHandleThrowsExceptionIfFactoryMissingForArrayReturn(): void
    {
        $controller = new class {
            public function action()
            {
                return ['foo' => 'bar'];
            }
        };

        $this->request
            ->method('getAttribute')
            ->willReturnMap([
                ['_classname', null, 'C'],
                ['_method', null, 'action'],
                ['_params', [], []],
            ]);

        $c = $this->createStub(ContainerInterface::class);
        $c->method('has')->willReturnMap([
            ['C',                             true],
            [ResponseFactoryInterface::class, false],
        ]); // Factory missing
        $c->method('get')->willReturnMap([['C', $controller]]);

        $d = new ControllerDispatcher($c);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no conversion strategy matched');

        $d->handle($this->request);
    }
}
