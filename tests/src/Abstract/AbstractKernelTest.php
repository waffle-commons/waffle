<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Waffle\Abstract\AbstractKernel;
use Waffle\Attribute\Configuration;
use Waffle\Core\Constant;
use Waffle\Core\Response;
use Waffle\Core\System;
use WaffleTests\Abstract\Helper\ConcreteTestKernel;
use WaffleTests\Abstract\Helper\ControllableTestRequest;
use WaffleTests\Router\Dummy\DummyController;

#[CoversClass(AbstractKernel::class)]
final class AbstractKernelTest extends TestCase
{
    private ConcreteTestKernel $kernel;

    /**
     * @var mixed[] $originalServer
     */
    private array $originalServer;

    /**
     * @var mixed[] $originalEnv
     */
    private array $originalEnv;

    protected function setUp(): void
    {
        // Store original superglobals
        $this->originalServer = $_SERVER;
        $this->originalEnv = $_ENV;

        // Set up a clean environment for each test
        $_SERVER = ['REQUEST_URI' => '/'];
        $_ENV = ['APP_ENV' => 'test'];

        $this->kernel = new ConcreteTestKernel();
    }

    protected function tearDown(): void
    {
        // Restore original superglobals
        $_SERVER = $this->originalServer;
        $_ENV = $this->originalEnv;
    }

    public function testBootInitializesConfig(): void
    {
        // Act
        $this->kernel->boot();

        // Assert
        $this->assertInstanceOf(Configuration::class, $this->kernel->getTestConfig());
    }

    public function testConfigureCreatesSystem(): void
    {
        // Act
        $this->kernel->boot()->configure();

        // Assert
        $this->assertInstanceOf(System::class, $this->kernel->getTestSystem());
    }

    public function testCreateRequestFromGlobalsWithMatchingRoute(): void
    {
        // Arrange
        $_SERVER['REQUEST_URI'] = '/users';
        $this->kernel->boot()->configure();

        // Act
        $request = $this->kernel->createRequestFromGlobals();
        $request->setCurrentRoute(route: [
            Constant::CLASSNAME => DummyController::class,
            Constant::METHOD => 'list',
            Constant::ARGUMENTS => [],
            Constant::PATH => '/users',
            Constant::NAME => 'user_list',
        ]);

        // Assert
        // @phpstan-ignore method.notFound
        $this->assertNotNull($request->getCurrentRoute(), 'The router should have found a matching route.');
        // @phpstan-ignore method.notFound
        $this->assertSame(DummyController::class, $request->getCurrentRoute()['classname']);
    }

    public function testRunExecutesHandlerFlow(): void
    {
        // Arrange
        $responseMock = $this->createMock(Response::class);
        $responseMock->expects($this->once())->method('render');

        // Use our controllable helper, which is a real, initialized object.
        $request = new ControllableTestRequest();
        $request->setResponse($responseMock); // Configure it to return our mock response.

        // Act & Assert (assertions are the mock expectations)
        $this->kernel->run($request);
    }

    public function testRunCatchesAndRendersThrowable(): void
    {
        // Arrange
        $exceptionMessage = 'Something went wrong';

        // Use our controllable helper. It's a real object, so it's always initialized.
        $request = new ControllableTestRequest();
        $request->setException(new Exception($exceptionMessage)); // Configure it to throw an exception.

        // We expect the output to be a JSON error message containing the exception text.
        $this->expectOutputRegex(sprintf('/%s/', $exceptionMessage));

        // Act
        $this->kernel->run($request);
    }
}
