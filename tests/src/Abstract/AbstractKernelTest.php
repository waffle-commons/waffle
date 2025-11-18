<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Waffle\Commons\Container\Container as CommonsContainer;
use Waffle\Commons\Http\Response;
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
    private null|CommonsContainer $innerContainer = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->originalAppEnv = $_ENV[Constant::APP_ENV] ?? null;

        // 1. Create the raw PSR-11 container (from waffle-commons/container)
        // This simulates what the Runtime does.
        $this->innerContainer = new CommonsContainer();

        // 2. Instantiate WebKernel WITHOUT passing a legacy container in constructor.
        // We want to test the real boot/configure process that uses the inner container.
        $this->kernel = new WebKernel(
            configDir: $this->testConfigDir,
            environment: 'dev',
            container: null, // Important: null to trigger internal configuration
            innerContainer: $this->innerContainer, // Inject the PSR-11 container
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

        // Ensure config files exist (routes etc.)
        $this->createTestConfigFile(securityLevel: 2);

        $uri = new Uri('/users');
        $request = new ServerRequest('GET', $uri);

        // Act
        // We let handle() call boot()->configure().
        // This will use the $innerContainer injected in setUp() to build the System.
        $response = $this->kernel->handle($request);

        // Assert
        static::assertInstanceOf(ResponseInterface::class, $response);
        static::assertSame(200, $response->getStatusCode());
        static::assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $body = (string) $response->getBody();
        $expectedJson = '{"id":1,"name":"John Doe"}';
        static::assertJsonStringEqualsJsonString($expectedJson, $body);
    }

    public function testHandleCatchesAndRendersThrowable(): void
    {
        // Arrange
        $_ENV[Constant::APP_ENV] = 'dev';
        $this->createTestConfigFile(securityLevel: 2);

        $uri = new Uri('/trigger-error');
        $request = new ServerRequest('GET', $uri);

        // Act
        $response = $this->kernel->handle($request);

        // Assert
        static::assertInstanceOf(ResponseInterface::class, $response);
        static::assertSame(500, $response->getStatusCode());

        $data = json_decode((string) $response->getBody(), true);
        static::assertSame('Something went wrong', $data['message']);
    }

    public function testBootLoadsEnvironmentVariables(): void
    {
        // Arrange
        $envContent = "APP_TEST=test_boot\nANOTHER_VAR=waffle_test";
        $envPath = APP_ROOT . '/.env';
        file_put_contents($envPath, $envContent);

        // Act
        $this->kernel->boot();

        // Teardown
        unlink($envPath);

        // Assert
        static::assertSame('test_boot', getenv('APP_TEST'));
        static::assertSame('waffle_test', getenv('ANOTHER_VAR'));
    }

    /**
     * Covers createResponse() factory logic.
     */
    public function testHandleUsesResponseFactoryFromContainerIfAvailable(): void
    {
        $_ENV[Constant::APP_ENV] = 'dev';
        $this->createTestConfigFile(securityLevel: 2);

        // 1. Prepare Mocks
        $factoryMock = $this->createMock(ResponseFactoryInterface::class);
        $expectedResponse = new \Waffle\Commons\Http\Response(202);

        $factoryMock->expects($this->once())->method('createResponse')->with(200)->willReturn($expectedResponse);

        // 2. Inject into INNER container directly.
        // The AbstractKernel wraps this exact instance, so the definition persists.
        $this->innerContainer->set(ResponseFactoryInterface::class, $factoryMock);

        // 3. Pre-register the controller to ensure routing works without scanning
        // (ContainerFactory scanning happens in configure(), but manual set overrides/complements it)
        $this->innerContainer->set(
            \WaffleTests\Helper\Controller\TempController::class,
            \WaffleTests\Helper\Controller\TempController::class,
        );

        $uri = new Uri('/users');
        $request = new ServerRequest('GET', $uri);

        // Act
        $response = $this->kernel->handle($request);

        // Assert
        static::assertSame(202, $response->getStatusCode());
    }

    /**
     * Covers configure(), setContainerImplementation(), and createFailsafeContainer().
     */
    public function testFailsafeContainerIsUsedWhenConfigurationFails(): void
    {
        // 1. Setup a Kernel WITHOUT an inner container.
        // This simulates a critical error where the Runtime failed to inject the container.
        $kernel = new WebKernel(
            configDir: $this->testConfigDir,
            environment: 'prod',
            container: null,
            innerContainer: null, // CRITICAL: No container provided
        );

        $uri = new Uri('/users');
        $request = new ServerRequest('GET', $uri);

        // Act
        // handle() will call configure().
        // configure() checks for innerContainer, finds null, throws ContainerException.
        // handle() catches exception, calls handleException().
        // handleException() sees $this->container is null, calls createFailsafeContainer().
        $response = $kernel->handle($request);

        // Assert
        static::assertSame(500, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        static::assertTrue($body['error']);
        // The message comes from the exception thrown in configure()
        static::assertStringContainsString('No Container implementation provided', $body['message']);

        static::assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * Covers successful configuration with injected inner container.
     */
    public function testConfigureUsingInjectedContainer(): void
    {
        // This test verifies that boot()->configure() correctly sets up the Core Container wrapper.

        // Act
        $this->kernel->boot()->configure();

        // Assert
        static::assertNotNull($this->kernel->container);
        static::assertInstanceOf(\Waffle\Core\Container::class, $this->kernel->container);

        // We can check if Security was injected by trying to get something that triggers it,
        // or just trusting the type.
    }
}
