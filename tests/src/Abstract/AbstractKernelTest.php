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
        $this->originalAppEnv = $_ENV[Constant::APP_ENV] ?? null; // Backup APP_ENV

        $this->container = $this->createRealContainer(level: 2);

        // Instantiate our test-specific WebKernel but do not boot it yet.
        $this->kernel = new WebKernel(
            configDir: $this->testConfigDir,
            environment: 'dev',
            container: $this->container,
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        // Restore original APP_ENV
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

        // Create a PSR-7 request (instead of setting $_SERVER globals implicitly)
        $uri = new Uri('/users');
        $request = new ServerRequest('GET', $uri);

        // Act: Boot the kernel now that the environment is ready.
        $this->kernel?->boot()->configure();

        // Handle now returns a ResponseInterface object, it does not output to buffer directly.
        $response = $this->kernel?->handle($request);

        // Assert
        static::assertInstanceOf(ResponseInterface::class, $response);
        static::assertSame(200, $response->getStatusCode());
        static::assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $body = (string) $response->getBody();
        static::assertJson($body, 'Output was: ' . $body);

        $expectedJson = '{"id":1,"name":"John Doe"}'; // Matches TempController::list data
        static::assertJsonStringEqualsJsonString($expectedJson, $body);
    }

    public function testHandleCatchesAndRendersThrowable(): void
    {
        // Arrange
        $_ENV[Constant::APP_ENV] = 'dev';

        $uri = new Uri('/trigger-error');
        $request = new ServerRequest('GET', $uri);

        // Act: Boot the kernel.
        $this->kernel?->boot()->configure();

        // Handle returns the error response
        $response = $this->kernel?->handle($request);

        // Assert
        static::assertInstanceOf(ResponseInterface::class, $response);
        // Assuming handleException returns 500 for generic exceptions
        static::assertSame(500, $response->getStatusCode());
        static::assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $body = (string) $response->getBody();
        static::assertJson($body, 'Output was: ' . $body);

        // Robust JSON assertion
        $data = json_decode($body, true);
        static::assertArrayHasKey('message', $data);
        static::assertSame('Something went wrong', $data['message']);

        // In dev mode, we expect a trace
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
