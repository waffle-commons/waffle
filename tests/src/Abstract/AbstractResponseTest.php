<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Abstract\AbstractResponse;
use Waffle\Core\Constant;
use Waffle\Core\View;
use Waffle\Enum\HttpBag;
use Waffle\Exception\RenderingException;
use Waffle\Http\ParameterBag;
use Waffle\Interface\RequestInterface;
use WaffleTests\Abstract\Helper\ConcreteTestResponse;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\Controller\TempController;

#[CoversClass(AbstractResponse::class)]
final class AbstractResponseTest extends TestCase
{
    // Added setUp/tearDown to manage superglobals potentially modified by tests
    private array $serverBackup;
    private array $envBackup;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->serverBackup = $_SERVER;
        $this->envBackup = $_ENV;
        // Ensure a default test environment
        $_ENV['APP_ENV'] = Constant::ENV_TEST;
        $_SERVER['REQUEST_URI'] = '/';
    }

    #[\Override]
    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        $_ENV = $this->envBackup;
        parent::tearDown();
    }

    /**
     * Tests that the render method correctly calls the controller action.
     * @throws RenderingException
     */
    public function testRenderCallsControllerActionAndView(): void
    {
        // 1. Setup: Create a real test instance of our abstract class.
        $_ENV['APP_ENV'] = Constant::ENV_DEV;
        $_SERVER['REQUEST_URI'] = '/users';
        $requestHandler = $this->createRealRequest(
            level: 2,
            globals: [
                'server' => $_SERVER ?? [],
                'get' => $_GET ?? [],
                'post' => $_POST ?? [],
                'files' => $_FILES ?? [],
                'cookie' => $_COOKIE ?? [],
                'session' => $_SESSION ?? [],
                'request' => $_GET ?? [],
                'env' => $_ENV ?? [],
            ],
        );
        $container = $requestHandler->container;
        $container?->set(TempController::class, TempController::class);

        // 2. Configure the object state using its public methods.
        $requestHandler->setCurrentRoute(route: [
            Constant::CLASSNAME => TempController::class,
            Constant::METHOD => 'list',
            Constant::ARGUMENTS => [],
            Constant::PATH => '/users',
            Constant::NAME => 'user_list',
        ]);

        // 3. Create the response object with our configured handler.
        $response = new ConcreteTestResponse(handler: $requestHandler);

        // 4. Action: Call the render method.
        ob_start(); // Capture output
        $response->render();
        $output = ob_get_clean() ?? '';

        // 5. Assert: Check the result.
        static::assertInstanceOf(View::class, $response->getView());
        $expectedData = [
            'id' => 1,
            'name' => 'John Doe',
        ];
        static::assertSame($expectedData, $response->getView()?->data);
        static::assertSame($expectedData, $response->getView()?->data); // Use assertEquals for arrays
        static::assertJson($output, 'Render should output JSON in dev env');
        static::assertStringContainsString('"id": 1', $output);
        static::assertStringContainsString('"name": "John Doe"', $output);
    }

    /**
     * Tests that rendering throws an exception for a type mismatch in URL parameters.
     */
    public function testRenderThrowsExceptionForMismatchedArgumentType(): void
    {
        static::expectException(RenderingException::class);
        static::expectExceptionCode(400);
        static::expectExceptionMessage('URL parameter "id" expects type int, got invalid value: "abc".');

        // Setup
        $_ENV['APP_ENV'] = Constant::ENV_DEV;
        $_SERVER['REQUEST_URI'] = '/users/abc';
        $requestHandler = $this->createRealRequest(
            level: 2,
            globals: [
                'server' => $_SERVER ?? [],
                'get' => $_GET ?? [],
                'post' => $_POST ?? [],
                'files' => $_FILES ?? [],
                'cookie' => $_COOKIE ?? [],
                'session' => $_SESSION ?? [],
                'request' => $_GET ?? [],
                'env' => $_ENV ?? [],
            ],
        );
        $container = $requestHandler->container;
        $container?->set(TempController::class, TempController::class);
        $requestHandler->setCurrentRoute(route: [
            Constant::CLASSNAME => TempController::class,
            Constant::METHOD => 'show',
            Constant::ARGUMENTS => ['id' => 'int'],
            Constant::PATH => '/users/{id}',
            Constant::NAME => 'user_show',
        ]);

        $response = new ConcreteTestResponse(handler: $requestHandler);

        // Action
        $response->render();
    }

    /**
     * Test case where the route provided to the Response handler is null.
     * render() should do nothing and not produce output.
     */
    public function testRenderHandlesNullRoute(): void
    {
        // Setup
        // Create a mock handler (RequestInterface)
        $handlerMock = $this->createMock(RequestInterface::class);
        // Explicitly set the currentRoute property to null on the mock
        $handlerMock->currentRoute = null;
        // Mock other properties/methods needed by ConcreteTestResponse constructor/render
        $handlerMock->container = $this->createRealContainer(); // Provide a container
        // Mock bag() to return environment config
        $handlerMock
            ->method('bag')
            ->with(static::equalTo(HttpBag::ENV))
            ->willReturn($this->createParameterBag(['APP_ENV' => Constant::ENV_DEV])); // Specify ENV bag

        $response = new ConcreteTestResponse(handler: $handlerMock);

        // Action
        ob_start();
        $response->render();
        $output = ob_get_clean() ?? '';

        // Assertions
        static::assertEmpty($output, 'No output should be generated when the route is null.');

        // ConcreteTestResponse::getView always returns a view, let's check AbstractResponse behavior implicitly
        // If callControllerAction returned null, rendering would be skipped.
    }

    /**
     * Test case where the route points to a controller class not registered in the container.
     * render() should do nothing gracefully.
     */
    public function testRenderHandlesMissingControllerInContainer(): void
    {
        // Setup
        $container = $this->createRealContainer(); // Create container WITHOUT TempController
        $handlerMock = $this->createMock(RequestInterface::class);
        $handlerMock->currentRoute = [ // Route points to TempController
            Constant::CLASSNAME => TempController::class,
            Constant::METHOD => 'list',
            Constant::ARGUMENTS => [],
            Constant::PATH => '/users',
            Constant::NAME => 'user_list',
        ];
        $handlerMock->container = $container; // Assign the container lacking the controller
        $handlerMock
            ->method('bag')
            ->with(static::equalTo(HttpBag::ENV))
            ->willReturn($this->createParameterBag(['APP_ENV' => Constant::ENV_DEV]));

        $response = new ConcreteTestResponse(handler: $handlerMock);

        // Action (Expecting no error, just no output)
        ob_start();
        $response->render();
        $output = ob_get_clean() ?? '';

        // Assertions
        static::assertEmpty($output, 'No output should be generated when controller is not in container.');
    }

    /**
     * Test case where the route points to a valid controller, but an invalid method.
     * render() should do nothing gracefully.
     */
    public function testRenderHandlesMissingMethodInController(): void
    {
        // Setup
        $container = $this->createRealContainer();
        $container->set(TempController::class, TempController::class); // Controller IS registered
        $handlerMock = $this->createMock(RequestInterface::class);
        $handlerMock->currentRoute = [ // Route points to non-existent method
            Constant::CLASSNAME => TempController::class,
            Constant::METHOD => 'nonExistentMethod', // Invalid method
            Constant::ARGUMENTS => [],
            Constant::PATH => '/invalid',
            Constant::NAME => 'invalid_route',
        ];
        $handlerMock->container = $container;
        $handlerMock
            ->method('bag')
            ->with(static::equalTo(HttpBag::ENV))
            ->willReturn($this->createParameterBag(['APP_ENV' => Constant::ENV_DEV]));

        $response = new ConcreteTestResponse(handler: $handlerMock);

        // Action (Expecting no error, just no output)
        ob_start();
        $response->render();
        $output = ob_get_clean() ?? '';

        // Assertions
        static::assertEmpty($output, 'No output should be generated when method does not exist.');
    }

    // Helper to create a ParameterBag mock easily
    private function createParameterBag(array $params = []): ParameterBag
    {
        return new ParameterBag($params); // Use real ParameterBag for simplicity
    }
}
