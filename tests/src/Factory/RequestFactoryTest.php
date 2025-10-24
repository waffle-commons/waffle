<?php

declare(strict_types=1);

namespace WaffleTests\Factory;

use phpmock\phpunit\PHPMock; // Import the trait for mocking global functions
use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Core\Container;
use Waffle\Core\Request;
use Waffle\Core\Security;
use Waffle\Core\System;
use Waffle\Enum\AppMode;
use Waffle\Enum\HttpBag;
use Waffle\Factory\RequestFactory;
use Waffle\Router\Router; // Keep the use statement
use WaffleTests\AbstractTestCase as TestCase;

#[CoversClass(RequestFactory::class)]
final class RequestFactoryTest extends TestCase
{
    use PHPMock; // Use the trait to enable function mocking

    private array $serverBackup;
    private array $getBackup;
    private array $postBackup;
    private array $filesBackup;
    private array $cookieBackup;
    private array $sessionBackup;
    private array $envBackup;
    private string $testControllerDir; // Directory for dummy routes

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        // Backup superglobals
        $this->serverBackup = $_SERVER;
        $this->getBackup = $_GET;
        $this->postBackup = $_POST;
        $this->filesBackup = $_FILES;
        $this->cookieBackup = $_COOKIE;
        $this->sessionBackup = $_SESSION ?? []; // Handle session potentially not started
        $this->envBackup = $_ENV;
        // Define a directory containing TempController for router booting
        $this->testControllerDir = APP_ROOT . '/tests/src/Helper';
    }

    #[\Override]
    protected function tearDown(): void
    {
        // Restore superglobals
        $_SERVER = $this->serverBackup;
        $_GET = $this->getBackup;
        $_POST = $this->postBackup;
        $_FILES = $this->filesBackup;
        $_COOKIE = $this->cookieBackup;
        if (isset($_SESSION)) { // Restore only if it was set
            $_SESSION = $this->sessionBackup;
        }
        $_ENV = $this->envBackup;
        parent::tearDown();
    }

    public function testCreateFromGlobalsHandlesGetRequest(): void
    {
        // --- Setup ---
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users'; // Use a route defined in TempController
        $_GET = [];
        $_POST = [];

        $factory = new RequestFactory();
        $container = $this->createRealContainer(level: 1); // Low level is fine
        $systemMockPassedToFactory = $this->createMock(System::class); // Mock for RequestFactory

        // --- Instantiate a REAL Router ---
        // Create a mock for the Security dependency needed by the real System
        $securityMockForRouterSystem = $this->createMock(Security::class);
        // Expect 'analyze' to be called by Router::match via the real System
        $securityMockForRouterSystem->expects($this->once())->method('analyze');
        // --- Create a REAL System instance, injecting the Security mock ---
        $routerRealSystem = new System($securityMockForRouterSystem);

        $router = new Router(
            directory: $this->testControllerDir,
            system: $routerRealSystem, // Pass the REAL System (with mock Security) to the real Router
        );
        // Boot the router to discover routes from TempController
        $router->boot($container);

        // Configure the System mock PASSED TO RequestFactory
        $systemMockPassedToFactory->method('getRouter')->willReturn($router); // Return the REAL router instance

        // Mock file_get_contents to ensure it's NOT called for php://input on GET
        $fileGetContentsMock = $this->getFunctionMock('Waffle\\Factory', 'file_get_contents');
        $fileGetContentsMock->expects($this->never()); // Assert it's never called

        // --- Action ---
        $request = $factory->createFromGlobals($container, $systemMockPassedToFactory);

        // --- Assertions ---
        static::assertInstanceOf(Request::class, $request);
        static::assertEmpty($request->query->all(), 'Query bag should be empty for this route.');
        static::assertEmpty($request->request->all(), 'Request bag (POST) should be empty.');
        static::assertNotNull($request->currentRoute, 'A route should have been matched and set.');
        static::assertSame('/users', $request->currentRoute['path']); // Verify correct route
    }

    public function testCreateFromGlobalsHandlesJsonPost(): void
    {
        // --- Setup ---
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['REQUEST_URI'] = '/trigger-error'; // Use another route from TempController
        $_GET = [];
        $_POST = [];

        $jsonData = ['user' => 'test-json', 'id' => 456];
        $jsonString = json_encode($jsonData);

        $factory = new RequestFactory();
        $container = $this->createRealContainer(level: 1);
        $systemMockPassedToFactory = $this->createMock(System::class); // Mock for RequestFactory

        // --- Instantiate a REAL Router ---
        $securityMockForRouterSystem = $this->createMock(Security::class);
        $securityMockForRouterSystem->expects($this->once())->method('analyze');
        // --- Create a REAL System instance, injecting the Security mock ---
        $routerRealSystem = new System($securityMockForRouterSystem);

        $router = new Router(
            directory: $this->testControllerDir,
            system: $routerRealSystem,
        );
        $router->boot($container);

        // Configure the System mock PASSED TO RequestFactory
        $systemMockPassedToFactory->method('getRouter')->willReturn($router);

        // Mock file_get_contents specifically for 'php://input'
        $fileGetContentsMock = $this->getFunctionMock('Waffle\\Factory', 'file_get_contents');
        $fileGetContentsMock->expects($this->once())->with(static::equalTo('php://input'))->willReturn($jsonString);

        // --- Action ---
        $request = $factory->createFromGlobals($container, $systemMockPassedToFactory);

        // --- Assertions ---
        static::assertInstanceOf(Request::class, $request);
        static::assertEmpty($request->query->all());
        static::assertSame($jsonData, $request->request->all(), 'Request bag should contain decoded JSON data.');
        static::assertSame($jsonData, $request->bag(HttpBag::REQUEST)->all());
        static::assertNotNull($request->currentRoute);
        static::assertSame('/trigger-error', $request->currentRoute['path']);
    }

    public function testCreateFromGlobalsHandlesUrlEncodedPost(): void
    {
        // --- Setup ---
        $_SERVER['REQUEST_METHOD'] = 'POST';
        // Use a route expecting parameters to ensure matching works
        $_SERVER['REQUEST_URI'] = '/users/99/test-slug';
        $_GET = [];
        $postData = ['field1' => 'data1', 'field2' => 'data2'];
        $_POST = $postData;

        $factory = new RequestFactory();
        $container = $this->createRealContainer(level: 1);
        $systemMockPassedToFactory = $this->createMock(System::class); // Mock for RequestFactory

        // --- Instantiate a REAL Router ---
        $securityMockForRouterSystem = $this->createMock(Security::class);
        $securityMockForRouterSystem->expects($this->once())->method('analyze');
        // --- Create a REAL System instance, injecting the Security mock ---
        $routerRealSystem = new System($securityMockForRouterSystem);

        $router = new Router(
            directory: $this->testControllerDir,
            system: $routerRealSystem,
        );
        $router->boot($container);

        // Configure the System mock PASSED TO RequestFactory
        $systemMockPassedToFactory->method('getRouter')->willReturn($router);

        // Mock file_get_contents to ensure it's NOT called
        $fileGetContentsMock = $this->getFunctionMock('Waffle\\Factory', 'file_get_contents');
        $fileGetContentsMock->expects($this->never());

        // --- Action ---
        $request = $factory->createFromGlobals($container, $systemMockPassedToFactory);

        // --- Assertions ---
        static::assertInstanceOf(Request::class, $request);
        static::assertEmpty($request->query->all());
        static::assertSame($postData, $request->request->all(), 'Request bag should contain POST data.');
        static::assertSame($postData, $request->bag(HttpBag::REQUEST)->all());
        static::assertNotNull($request->currentRoute);
        static::assertSame('/users/{id}/{slug}', $request->currentRoute['path']);
    }

    public function testCreateFromGlobalsSetsRouteToNullWhenNoMatch(): void
    {
        // --- Setup ---
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/not-found';
        $_GET = [];
        $_POST = [];

        $factory = new RequestFactory();
        $container = $this->createRealContainer(level: 1);
        $systemMockPassedToFactory = $this->createMock(System::class); // Mock for RequestFactory

        // --- Instantiate a REAL Router ---
        $securityMockForRouterSystem = $this->createMock(Security::class);
        // Analyze won't be called if no match
        $securityMockForRouterSystem->expects($this->never())->method('analyze');
        // --- Create a REAL System instance, injecting the Security mock ---
        $routerRealSystem = new System($securityMockForRouterSystem);

        $router = new Router(
            directory: $this->testControllerDir,
            system: $routerRealSystem,
        );
        $router->boot($container);

        // Configure the System mock PASSED TO RequestFactory
        $systemMockPassedToFactory->method('getRouter')->willReturn($router);

        // Mock file_get_contents to ensure it's NOT called
        $fileGetContentsMock = $this->getFunctionMock('Waffle\\Factory', 'file_get_contents');
        $fileGetContentsMock->expects($this->never());

        // --- Action ---
        $request = $factory->createFromGlobals($container, $systemMockPassedToFactory);

        // --- Assertions ---
        static::assertInstanceOf(Request::class, $request);
        static::assertNull($request->currentRoute, 'Current route should be null when no route matches.');
    }
}
