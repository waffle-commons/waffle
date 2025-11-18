<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use Psr\Http\Message\ResponseInterface;
use Waffle\Commons\Http\ServerRequest;
use Waffle\Commons\Http\Uri;
use Waffle\Core\Constant;
use Waffle\Interface\ContainerInterface;
use WaffleTests\Abstract\Helper\WebKernel;
use WaffleTests\AbstractTestCase as TestCase;

final class AbstractKernelTest extends TestCase
{
    private null|ContainerInterface $container = null;
    private null|WebKernel $kernel = null;
    private null|string $originalAppEnv = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->originalAppEnv = $_ENV[Constant::APP_ENV] ?? null;

        $this->container = $this->createRealContainer(level: 2);

        $this->kernel = new WebKernel(
            configDir: $this->testConfigDir,
            environment: 'dev',
            container: $this->container,
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
        // Arrange
        $_ENV[Constant::APP_ENV] = 'dev';

        $uri = new Uri('/users');
        $request = new ServerRequest('GET', $uri);

        // Act
        $this->kernel?->boot()->configure();
        $response = $this->kernel?->handle($request);

        // Assert
        static::assertInstanceOf(ResponseInterface::class, $response);
        static::assertSame(200, $response->getStatusCode());
        static::assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $body = (string) $response->getBody();
        static::assertJson($body);

        // Use flexible JSON assertion
        $expectedJson = '{"id":1,"name":"John Doe"}';
        static::assertJsonStringEqualsJsonString($expectedJson, $body);
    }

    public function testHandleCatchesAndRendersThrowable(): void
    {
        // Arrange
        $_ENV[Constant::APP_ENV] = 'dev';

        $uri = new Uri('/trigger-error');
        $request = new ServerRequest('GET', $uri);

        // Act
        $this->kernel?->boot()->configure();
        $response = $this->kernel?->handle($request);

        // Assert
        static::assertInstanceOf(ResponseInterface::class, $response);
        static::assertSame(500, $response->getStatusCode());
        static::assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $body = (string) $response->getBody();
        static::assertJson($body);

        // Decode to array for robust testing
        $data = json_decode($body, true);

        static::assertArrayHasKey('error', $data);
        static::assertTrue($data['error']);
        static::assertArrayHasKey('message', $data);
        static::assertSame('Something went wrong', $data['message']);

        // In 'dev' environment, trace should be present
        static::assertArrayHasKey('trace', $data);
    }

    public function testBootLoadsEnvironmentVariables(): void
    {
        // Arrange
        $envContent = "APP_TEST=test_boot\nANOTHER_VAR=waffle_test";
        $envPath = APP_ROOT . '/.env';
        file_put_contents($envPath, $envContent);

        // Act
        $this->kernel?->boot();

        // Teardown
        unlink($envPath);

        // Assert
        static::assertSame('test_boot', getenv('APP_TEST'));
        static::assertSame('waffle_test', getenv('ANOTHER_VAR'));
    }
}
