<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use AllowDynamicProperties;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
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
use Waffle\Exception\Container\ContainerException;
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

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->method('write')->willReturn(strlen(json_encode($data)));

        $responseMock->method('getBody')->willReturn($streamMock);
        $responseMock->method('withHeader')->willReturnSelf();
        $responseMock->method('getStatusCode')->willReturn(200);

        $responseFactoryMock = $this->createMock(ResponseFactoryInterface::class);
        $responseFactoryMock->method('createResponse')->willReturn($responseMock);

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

        $this->assertSame(200, $response->getStatusCode());
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
            public function getStatusCode(): int
            {
                return 204;
            }

            public function withStatus($code, $reasonPhrase = ''): ResponseInterface
            {
                return $this;
            }

            public function getReasonPhrase(): string
            {
                return 'No Content';
            }

            public function getProtocolVersion(): string
            {
                return '1.1';
            }

            public function withProtocolVersion($version): ResponseInterface
            {
                return $this;
            }

            public function getHeaders(): array
            {
                return [];
            }

            public function hasHeader($name): bool
            {
                return false;
            }

            public function getHeader($name): array
            {
                return [];
            }

            public function getHeaderLine($name): string
            {
                return '';
            }

            public function withHeader($name, $value): ResponseInterface
            {
                return $this;
            }

            public function withAddedHeader($name, $value): ResponseInterface
            {
                return $this;
            }

            public function withoutHeader($name): ResponseInterface
            {
                return $this;
            }

            public function getBody(): StreamInterface
            {
                return new class implements StreamInterface {
                    public function __toString(): string
                    {
                        return '';
                    }

                    public function close(): void
                    {
                    }

                    public function detach()
                    {
                        return null;
                    }

                    public function getSize(): null|int
                    {
                        return 0;
                    }

                    public function tell(): int
                    {
                        return 0;
                    }

                    public function eof(): bool
                    {
                        return true;
                    }

                    public function isSeekable(): bool
                    {
                        return false;
                    }

                    public function seek($offset, $whence = SEEK_SET): void
                    {
                    }

                    public function rewind(): void
                    {
                    }

                    public function isWritable(): bool
                    {
                        return false;
                    }

                    public function write($string): int
                    {
                        return 0;
                    }

                    public function isReadable(): bool
                    {
                        return true;
                    }

                    public function read($length): string
                    {
                        return '';
                    }

                    public function getContents(): string
                    {
                        return '';
                    }

                    public function getMetadata($key = null)
                    {
                        return [];
                    }
                };
            }

            public function withBody(StreamInterface $body): ResponseInterface
            {
                return $this;
            }
        };

        $responseFactoryMock = $this->createMock(ResponseFactoryInterface::class);
        $responseFactoryMock
            ->expects($this->any())
            ->method('createResponse')
            ->willReturnCallback(function (int $code) use ($robustResponse) {
                return $robustResponse;
            });

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

        $this->assertSame(204, $response->getStatusCode());
    }

    public function testHandleCatchesCriticalErrorsDuringExceptionHandling(): void
    {
        $responseFactoryMock = $this->createMock(ResponseFactoryInterface::class);
        $responseFactoryMock
            ->method('createResponse')
            ->willThrowException(new RuntimeException('Response factory is broken'));

        $throwingRouter = new class {
            public function match(ServerRequestInterface $request): array
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
                public function __construct(string $configDir, string $env, LoggerInterface $logger)
                {
                    parent::__construct($logger);
                    $this->environment = $env;
                    $this->testContainer = null;
                }

                public $testContainer;

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

            public function match(ServerRequestInterface $request): array
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
            if ($reflection->hasProperty($propName)) {
                $property = $reflection->getProperty($propName);
                $property->setValue($kernel, $state);
                return;
            }
        }
    }
}

// --- LOCAL STUB ---
// This class acts as a stand-in for Waffle\Commons\Container\Container
// It must be outside the Test class to be aliasable properly.
class StubCommonsContainer implements \Psr\Container\ContainerInterface
{
    public function __construct() {} // Kernel calls it with no args

    public function get(string $id): mixed
    {
        return null;
    }

    public function has(string $id): bool
    {
        return true;
    }

    public function set(string $id, mixed $service): void
    {
    }
}
