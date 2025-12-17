<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use AllowDynamicProperties;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;
use RuntimeException;
use Waffle\Abstract\AbstractKernel;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Exception\Container\NotFoundException;
use Waffle\Router\Router;
use WaffleTests\Abstract\Helper\WebKernel;

/**
 * Targets specific logic branches in AbstractKernel not covered by the main test.
 */
#[CoversClass(AbstractKernel::class)]
#[AllowMockObjectsWithoutExpectations]
class AbstractKernelEdgeCaseTest extends TestCase
{
    protected string $testConfigDir;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->testConfigDir = __DIR__ . '/../../fixtures/config';
        if (!is_dir($this->testConfigDir)) {
            mkdir($this->testConfigDir, 0777, true);
        }

        if (!defined('APP_ROOT')) {
            define('APP_ROOT', sys_get_temp_dir());
        }
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (is_dir($this->testConfigDir)) {
            // Basic cleanup
        }
        $fakeAppDir = APP_ROOT . '/app';
        if (is_dir($fakeAppDir) && is_writable($fakeAppDir)) {
            @rmdir($fakeAppDir);
        }
        parent::tearDown();
    }

    public function testBootIsIdempotent(): void
    {
        $kernel = new WebKernel(
            configDir: $this->testConfigDir,
            environment: 'test',
        );

        $kernel->boot();
        $kernel->boot();

        $this->addToAssertionCount(1);
    }

    public function testHandleConvertsArrayResponseToJson(): void
    {
        $data = ['status' => 'ok', 'id' => 123];

        $controllerService = new class($data) {
            public function __construct(
                private array $data,
            ) {}

            public function __invoke()
            {
                return $this->data;
            }
        };

        /** @var ResponseInterface&MockObject $responseMock */
        $responseMock = $this->createMock(ResponseInterface::class);
        /** @var StreamInterface&MockObject $streamMock */
        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->method('write')->willReturn(strlen(json_encode($data)));

        $responseMock->method('getBody')->willReturn($streamMock);
        $responseMock->method('withHeader')->willReturnSelf();
        $responseMock->method('getStatusCode')->willReturn(200);

        /** @var ResponseFactoryInterface&MockObject $responseFactoryMock */
        $responseFactoryMock = $this->createMock(ResponseFactoryInterface::class);
        $responseFactoryMock->method('createResponse')->willReturn($responseMock);

        /** @var ContainerInterface&MockObject $containerMock */
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('has')->willReturn(true);

        $routerStub = $this->createManualRouterStub('TestController');

        $containerMock
            ->method('get')
            ->willReturnMap([
                ['TestController',                $controllerService],
                [Router::class,                   $routerStub],
                [ResponseFactoryInterface::class, $responseFactoryMock],
            ]);

        $kernel = new WebKernel(
            configDir: $this->testConfigDir,
            environment: 'test',
        );

        $this->injectContainer($kernel, $containerMock);
        $kernel->setSecurity($this->createMock(\Waffle\Commons\Contracts\Security\SecurityInterface::class));
        $this->setBootedState($kernel, true);

        // Inject Fake Stack with minimalistic routing
        $stack = new \WaffleTests\Abstract\Helper\FakeMiddlewareStack();
        $stack->add(new class implements \Psr\Http\Server\MiddlewareInterface {
            #[\Override]
            public function process(
                ServerRequestInterface $request,
                \Psr\Http\Server\RequestHandlerInterface $handler,
            ): ResponseInterface {
                // Manually set controller attribute to bypass routing logic for this unit test
                $request = $request->withAttribute('_classname', 'TestController');
                $request = $request->withAttribute('_method', '__invoke');
                return $handler->handle($request);
            }
        });
        $kernel->setMiddlewareStack($stack);

        $request = $this->createMockRequest();
        $response = $kernel->handle($request);

        static::assertSame(200, $response->getStatusCode());
    }

    public function testHandleConvertsNullResponseToNoContent(): void
    {
        $controllerService = new class {
            public function __invoke()
            {
                return null;
            }
        };

        $robustResponse = new class implements ResponseInterface {
            #[\Override]
            public function getStatusCode(): int
            {
                return 204;
            }

            #[\Override]
            public function withStatus($code, $reasonPhrase = ''): ResponseInterface
            {
                return $this;
            }

            #[\Override]
            public function getReasonPhrase(): string
            {
                return 'No Content';
            }

            #[\Override]
            public function getProtocolVersion(): string
            {
                return '1.1';
            }

            #[\Override]
            public function withProtocolVersion($version): ResponseInterface
            {
                return $this;
            }

            #[\Override]
            public function getHeaders(): array
            {
                return [];
            }

            #[\Override]
            public function hasHeader($name): bool
            {
                return false;
            }

            #[\Override]
            public function getHeader($name): array
            {
                return [];
            }

            #[\Override]
            public function getHeaderLine($name): string
            {
                return '';
            }

            #[\Override]
            public function withHeader($name, $value): ResponseInterface
            {
                return $this;
            }

            #[\Override]
            public function withAddedHeader($name, $value): ResponseInterface
            {
                return $this;
            }

            #[\Override]
            public function withoutHeader($name): ResponseInterface
            {
                return $this;
            }

            #[\Override]
            public function getBody(): StreamInterface
            {
                return new class implements StreamInterface {
                    #[\Override]
                    public function __toString(): string
                    {
                        return '';
                    }

                    #[\Override]
                    public function close(): void
                    {
                    }

                    #[\Override]
                    public function detach()
                    {
                        return null;
                    }

                    #[\Override]
                    public function getSize(): null|int
                    {
                        return 0;
                    }

                    #[\Override]
                    public function tell(): int
                    {
                        return 0;
                    }

                    #[\Override]
                    public function eof(): bool
                    {
                        return true;
                    }

                    #[\Override]
                    public function isSeekable(): bool
                    {
                        return false;
                    }

                    #[\Override]
                    public function seek($offset, $whence = SEEK_SET): void
                    {
                    }

                    #[\Override]
                    public function rewind(): void
                    {
                    }

                    #[\Override]
                    public function isWritable(): bool
                    {
                        return false;
                    }

                    #[\Override]
                    public function write($string): int
                    {
                        return 0;
                    }

                    #[\Override]
                    public function isReadable(): bool
                    {
                        return true;
                    }

                    #[\Override]
                    public function read($length): string
                    {
                        return '';
                    }

                    #[\Override]
                    public function getContents(): string
                    {
                        return '';
                    }

                    #[\Override]
                    public function getMetadata($key = null)
                    {
                        return [];
                    }
                };
            }

            #[\Override]
            public function withBody(StreamInterface $body): ResponseInterface
            {
                return $this;
            }
        };

        $responseFactoryMock = $this->createMock(ResponseFactoryInterface::class);
        $responseFactoryMock
            ->expects($this->any())
            ->method('createResponse')
            ->willReturnCallback(static fn(int $_code) => $robustResponse);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('has')->willReturn(true);

        $routerStub = $this->createManualRouterStub('TestController');

        $containerMock
            ->method('get')
            ->willReturnMap([
                ['TestController',                $controllerService],
                [Router::class,                   $routerStub],
                [ResponseFactoryInterface::class, $responseFactoryMock],
            ]);

        $kernel = new WebKernel(
            configDir: $this->testConfigDir,
            environment: 'test',
        );

        $this->injectContainer($kernel, $containerMock);
        $kernel->setSecurity($this->createMock(\Waffle\Commons\Contracts\Security\SecurityInterface::class));
        $this->setBootedState($kernel, true);

        // Inject Fake Stack
        $stack = new \WaffleTests\Abstract\Helper\FakeMiddlewareStack();
        $stack->add(new class implements \Psr\Http\Server\MiddlewareInterface {
            #[\Override]
            public function process(
                ServerRequestInterface $request,
                \Psr\Http\Server\RequestHandlerInterface $handler,
            ): ResponseInterface {
                $request = $request->withAttribute('_classname', 'TestController');
                $request = $request->withAttribute('_method', '__invoke');
                return $handler->handle($request);
            }
        });
        $kernel->setMiddlewareStack($stack);

        $request = $this->createMockRequest();
        $response = $kernel->handle($request);

        static::assertSame(204, $response->getStatusCode());
    }

    public function testHandleCatchesCriticalErrorsDuringExceptionHandling(): void
    {
        $responseFactoryMock = $this->createMock(ResponseFactoryInterface::class);
        $responseFactoryMock
            ->method('createResponse')
            ->willThrowException(new RuntimeException('Response factory is broken'));

        $throwingRouter = new class {
            public function match(ServerRequestInterface $_request): array
            {
                throw new \Exception('Initial error to trigger handleException');
            }
        };

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('has')->willReturn(true);
        $containerMock
            ->method('get')
            ->willReturnMap([
                [Router::class,                   $throwingRouter],
                [ResponseFactoryInterface::class, $responseFactoryMock],
            ]);

        $kernel =
            new #[AllowDynamicProperties]
            class($this->testConfigDir, 'test', new NullLogger()) extends AbstractKernel {
                public function __construct(string $_configDir, string $env, LoggerInterface $logger)
                {
                    parent::__construct($logger);
                    $this->environment = $env;
                    $this->testContainer = null;
                }

                public $testContainer;

                #[\Override]
                public function boot(): AbstractKernel
                {
                    return $this;
                }

                #[\Override]
                public function configure(): self
                {
                    if ($this->testContainer) {
                        $this->container = $this->testContainer;
                    }

                    return $this;
                }
            };

        // Use setContainerImplementation ...
        $kernel->setContainerImplementation($containerMock);
        $kernel->testContainer = $containerMock;

        // Inject Config to pass configure() check
        $configMock = $this->createMock(\Waffle\Commons\Contracts\Config\ConfigInterface::class);
        $kernel->setConfiguration($configMock);
        $kernel->setSecurity($this->createMock(\Waffle\Commons\Contracts\Security\SecurityInterface::class));

        $this->setBootedState($kernel, true);

        // Ensure stack is set to avoid "MiddlewareStack not initialized" error before the one we expect
        $kernel->setMiddlewareStack(new \WaffleTests\Abstract\Helper\FakeMiddlewareStack());

        $request = $this->createMockRequest();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('System not initialized.');

        $kernel->configure();
        $kernel->handle($request);
    }

    // --- Helpers ---

    private function createMockRequest(): ServerRequestInterface
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/test');

        return new ServerRequest('GET', '/test');
    }

    private function createManualRouterStub(string $controllerName): object
    {
        return new class($controllerName) {
            public function __construct(
                private string $controller,
            ) {}

            public function match(ServerRequestInterface $_request): array
            {
                return [
                    'controller' => $this->controller,
                    'method' => '__invoke',
                    'arguments' => [],
                ];
            }
        };
    }

    private function injectContainer(object $object, object $container): void
    {
        $reflection = new ReflectionClass(AbstractKernel::class);
        $property = $reflection->getProperty('innerContainer');
        $property->setValue($object, $container);
    }

    private function setBootedState(AbstractKernel $kernel, bool $state): void
    {
        $reflection = new ReflectionClass(AbstractKernel::class);
        $props = ['isBooted', 'booted'];
        foreach ($props as $propName) {
            if (!$reflection->hasProperty($propName)) {
                continue;
            }

            $property = $reflection->getProperty($propName);
            $property->setValue($kernel, $state);
            return;
        }
    }
}

// --- LOCAL STUB ---
// This class acts as a stand-in for Waffle\Commons\Container\Container
// It must be outside the Test class to be aliasable properly.
class StubCommonsContainer implements \Psr\Container\ContainerInterface
{
    public function __construct() {} // Kernel calls it with no args

    #[\Override]
    public function get(string $id): mixed
    {
        return null;
    }

    #[\Override]
    public function has(string $id): bool
    {
        return true;
    }

    public function set(string $_id, mixed $_service): void
    {
    }
}
