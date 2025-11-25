<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Waffle\Core\Constant;
use Waffle\Interface\ContainerInterface;
use WaffleTests\Abstract\Helper\WebKernel;
use WaffleTests\AbstractTestCase as TestCase;

// --- Local Stubs to ensure stable behavior ---

class StubStream implements StreamInterface
{
    private string $content = '';

    public function __toString(): string
    {
        return $this->content;
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
        return strlen($this->content);
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
        return true;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
    }

    public function rewind(): void
    {
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function write(string $string): int
    {
        $this->content .= $string;
        return strlen($string);
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        return $this->content;
    }

    public function getContents(): string
    {
        return $this->content;
    }

    public function getMetadata(null|string $key = null)
    {
        return null;
    }
}

class StubResponse implements ResponseInterface
{
    private int $statusCode;
    private StreamInterface $body;

    public function __construct(int $code = 200)
    {
        $this->statusCode = $code;
        $this->body = new StubStream();
    }

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion(string $version): ResponseInterface
    {
        return $this;
    }

    public function getHeaders(): array
    {
        return [];
    }

    public function hasHeader(string $name): bool
    {
        return false;
    }

    public function getHeader(string $name): array
    {
        return [];
    }

    public function getHeaderLine(string $name): string
    {
        return '';
    }

    public function withHeader(string $name, $value): ResponseInterface
    {
        return $this;
    }

    public function withAddedHeader(string $name, $value): ResponseInterface
    {
        return $this;
    }

    public function withoutHeader(string $name): ResponseInterface
    {
        return $this;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): ResponseInterface
    {
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        return $this;
    }

    public function getReasonPhrase(): string
    {
        return '';
    }
}

class StubContainer implements PsrContainerInterface
{
    public array $services = [];

    public function get(string $id)
    {
        return $this->services[$id] ?? null;
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }

    public function set(string $id, mixed $concrete): void
    {
        // CRITICAL FIX: Prevent ContainerFactory from overwriting our pre-configured objects
        // with class name strings found during scanning.
        if (isset($this->services[$id]) && is_object($this->services[$id]) && is_string($concrete)) {
            return;
        }
        $this->services[$id] = $concrete;
    }
}

final class AbstractKernelTest extends TestCase
{
    private null|WebKernel $kernel = null;
    private null|string $originalAppEnv = null;

    private StubContainer $innerContainer;
    private $responseFactoryMock;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->originalAppEnv = $_ENV[Constant::APP_ENV] ?? null;

        $this->innerContainer = new StubContainer();
        $this->responseFactoryMock = $this->createMock(ResponseFactoryInterface::class);

        $this->kernel = new WebKernel(
            configDir: $this->testConfigDir,
            environment: 'dev',
            container: null,
            innerContainer: $this->innerContainer,
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        $_ENV[Constant::APP_ENV] = $this->originalAppEnv;
        if ($this->originalAppEnv === null) {
            unset($_ENV[Constant::APP_ENV]);
        }
        $this->kernel = null;
        parent::tearDown();
    }

    public function testHandleWithMatchingRouteRendersResponse(): void
    {
        $_ENV[Constant::APP_ENV] = 'dev';
        $this->createTestConfigFile(securityLevel: 2);

        $uriMock = $this->createMock(UriInterface::class);
        $uriMock->method('getPath')->willReturn('/users');
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getUri')->willReturn($uriMock);

        $responseStub = new StubResponse(200);
        $this->responseFactoryMock->method('createResponse')->willReturn($responseStub);

        // Setup container
        $this->innerContainer->services[ResponseFactoryInterface::class] = $this->responseFactoryMock;
        $this->innerContainer->services['WaffleTests\Helper\Controller\TempController'] =
            new \WaffleTests\Helper\Controller\TempController();

        $response = $this->kernel->handle($requestMock);

        static::assertSame(200, $response->getStatusCode());
        static::assertJsonStringEqualsJsonString('{"id":1,"name":"John Doe"}', (string) $response->getBody());
    }

    public function testHandleUsesResponseFactoryFromContainerIfAvailable(): void
    {
        $_ENV[Constant::APP_ENV] = 'dev';
        $this->createTestConfigFile(securityLevel: 2);

        $uriMock = $this->createMock(UriInterface::class);
        $uriMock->method('getPath')->willReturn('/users');
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getUri')->willReturn($uriMock);

        $responseStub = new StubResponse(202);

        // Expectation
        $this->responseFactoryMock
            ->expects($this->once())
            ->method('createResponse')
            ->with(200)
            ->willReturn($responseStub);

        // Setup container
        $this->innerContainer->services[ResponseFactoryInterface::class] = $this->responseFactoryMock;
        $this->innerContainer->services['WaffleTests\Helper\Controller\TempController'] =
            new \WaffleTests\Helper\Controller\TempController();

        $response = $this->kernel->handle($requestMock);

        static::assertSame(202, $response->getStatusCode());
    }

    public function testHandleCatchesAndRendersThrowable(): void
    {
        $_ENV[Constant::APP_ENV] = 'dev';
        $this->createTestConfigFile(securityLevel: 2);

        $uriMock = $this->createMock(UriInterface::class);
        $uriMock->method('getPath')->willReturn('/trigger-error');
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getUri')->willReturn($uriMock);

        $responseStub = new StubResponse(500);
        $this->responseFactoryMock
            ->method('createResponse')
            ->with(500)
            ->willReturn($responseStub);

        // Setup container
        $this->innerContainer->services[ResponseFactoryInterface::class] = $this->responseFactoryMock;
        $this->innerContainer->services['WaffleTests\Helper\Controller\TempController'] =
            new \WaffleTests\Helper\Controller\TempController();

        $response = $this->kernel->handle($requestMock);

        static::assertSame(500, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        static::assertSame('Something went wrong', $body['message']);
    }

    public function testFailsafeContainerIsUsedWhenConfigurationFails(): void
    {
        $failingKernel = new WebKernel(
            configDir: $this->testConfigDir,
            environment: 'prod',
            container: null,
            innerContainer: null,
        );

        $uriMock = $this->createMock(UriInterface::class);
        $uriMock->method('getPath')->willReturn('/users');
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getUri')->willReturn($uriMock);

        // If Waffle\Commons\Http\Response exists, it returns 500.
        // If not, it throws RuntimeException.
        if (class_exists('Waffle\Commons\Http\Response')) {
            $response = $failingKernel->handle($requestMock);
            static::assertSame(500, $response->getStatusCode());
        } else {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('No Response implementation found');
            $failingKernel->handle($requestMock);
        }
    }

    public function testBootLoadsEnvironmentVariables(): void
    {
        $envContent = "APP_TEST=test_boot\nANOTHER_VAR=waffle_test";
        $envPath = APP_ROOT . '/.env';
        file_put_contents($envPath, $envContent);

        $this->kernel->boot();
        unlink($envPath);

        static::assertSame('test_boot', getenv('APP_TEST'));
        static::assertSame('waffle_test', getenv('ANOTHER_VAR'));
    }
}
