<?php

declare(strict_types=1);

namespace WaffleTests\Router;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Waffle\Core\Request;
use Waffle\Core\Security;
use Waffle\Core\System;
use Waffle\Router\Router;
use WaffleTests\Router\Dummy\DummyController;
use WaffleTests\TestCase;

/**
 * This test class provides comprehensive coverage for the Waffle\Router\Router class.
 * It validates all critical functionalities, including route discovery, matching of static
 * and dynamic URLs, and caching.
 */
#[CoversClass(Router::class)]
final class RouterTest extends TestCase
{
    private Router $router;
    private string $dummyControllerDir;

    private array $serverBackup;
    private System $system;

    /**
     * This setup method is executed before each test. It prepares a clean and consistent
     * environment by creating a real System object with a mocked Security dependency.
     * This solves the initialization error and allows the Router to be tested correctly.
     */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Backup the global state to ensure test isolation.
        $this->serverBackup = $_SERVER;
        $this->dummyControllerDir = __DIR__ . '/Dummy';

        // 1. Mock the deepest dependency: Security.
        // The `analyze` method has a `void` return type, so we don't use `willReturn`.
        $securityMock = $this->createMock(Security::class);
        $securityMock->method('analyze');

        // 2. Create a REAL System instance, injecting the security mock.
        // This ensures the System object is correctly constructed and its properties are initialized.
        $this->system = new System($securityMock);

        // 3. Instantiate the Router with its dependencies.
        $this->router = new Router(
            directory: $this->dummyControllerDir,
            system: $this->system,
        );
    }

    /**
     * This tearDown method restores the global state after each test,
     * ensuring that tests do not interfere with each other.
     */
    #[\Override]
    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        parent::tearDown();
    }

    /**
     * This test verifies that the router can correctly scan a directory, find controller
     * files, parse their Route attributes, and build the internal routing table.
     */
    public function testRegisterRoutesDiscoversAndBuildsRoutes(): void
    {
        // Action: Execute the boot and registration process.
        $container = $this->createRealContainer(level: 2);
        $this->router->boot()->registerRoutes(container: $container);

        // Assertions:
        static::assertNotEmpty($this->router->routes, 'The router should have discovered at least one route.');
        static::assertCount(5, $this->router->routes, 'The router should have discovered exactly 5 routes.');

        $foundRoute = false;
        foreach ($this->router->routes as $route) {
            if ('user_users_list' === $route['name']) {
                $foundRoute = true;
                static::assertSame(DummyController::class, $route['classname']);
                static::assertSame('/users', $route['path']);
                static::assertSame('list', $route['method']);
                break;
            }
        }
        static::assertTrue($foundRoute, 'The specific "user_users_list" route was not found.');
    }

    /**
     * This test validates matching a simple, static URL.
     */
    public function testMatchWithStaticRoute(): void
    {
        $container = $this->createRealContainer(level: 2);
        $this->router->boot()->registerRoutes(container: $container);
        $_SERVER['REQUEST_URI'] = '/users';
        $request = new Request(container: $container);

        $matchingRoute = null;
        foreach ($this->router->routes as $route) {
            if ($this->router->match($container, $request, $route)) {
                $matchingRoute = $route;
                break;
            }
        }

        static::assertNotNull($matchingRoute, 'A matching route should have been found for /users.');
        static::assertSame('user_users_list', $matchingRoute['name'], 'The matched route has an incorrect name.');
    }

    #[DataProvider('dynamicRouteProvider')]
    public function testMatchWithDynamicParameterRoutes(string $url, string $expectedRouteName): void
    {
        $container = $this->createRealContainer(level: 2);
        $this->router->boot()->registerRoutes(container: $container);
        $_SERVER['REQUEST_URI'] = $url;
        $request = new Request(container: $container);

        $matchingRoute = null;
        foreach ($this->router->routes as $route) {
            if ($this->router->match($container, $request, $route)) {
                $matchingRoute = $route;
                break;
            }
        }

        static::assertNotNull($matchingRoute, "A matching route should have been found for {$url}.");
        static::assertSame($expectedRouteName, $matchingRoute['name']);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function dynamicRouteProvider(): array
    {
        return [
            'Single integer parameter' => ['/users/123', 'user_users_show'],
            'Multiple parameters (int and string)' => ['/users/42/john-doe-slug', 'user_users_details'],
        ];
    }

    /**
     * This test ensures that a URL that does not correspond to any route is correctly ignored.
     */
    public function testNoMatchForNonExistentRoute(): void
    {
        $container = $this->createRealContainer(level: 2);
        $this->router->boot()->registerRoutes(container: $container);
        $_SERVER['REQUEST_URI'] = '/non-existent-route';
        $request = new Request(container: $container);

        $matchingRoute = null;
        foreach ($this->router->routes as $route) {
            if ($this->router->match($container, $request, $route)) {
                $matchingRoute = $route;
                break;
            }
        }

        static::assertNull($matchingRoute, 'No route should have been found for /non-existent-route.');
    }

    /**
     * This test validates the production optimization feature of route caching.
     */
    public function testRouteCachingInProductionEnvironment(): void
    {
        putenv('APP_ENV=prod');
        $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'waffle_routes_cache.php';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }

        $container = $this->createRealContainer(level: 2);
        $this->router->boot()->registerRoutes(container: $container);
        static::assertFileExists($cacheFile, 'The router should have created a cache file.');

        /** @var array $cachedRoutes */
        $cachedRoutes = require $cacheFile;
        static::assertNotEmpty($cachedRoutes, 'The cache file should not be empty.');
        static::assertCount(5, $cachedRoutes, 'The cache file should contain the correct number of routes.');

        unlink($cacheFile);
        putenv('APP_ENV=test');
    }

    /**
     * This test validates that the router is robust and does not crash when provided with
     * a non-existent controller directory. A well-behaved framework should handle this
     * configuration error gracefully instead of throwing a fatal error.
     */
    public function testRouterHandlesNonExistentDirectoryGracefully(): void
    {
        // 1. Setup: Create a new Router instance pointing to a directory we know does not exist.
        $badRouter = new Router(
            directory: __DIR__ . '/NonExistentDirectory',
            system: $this->system,
        );

        // 2. Action: Execute the boot and registration process. This would crash if not handled.
        $container = $this->createRealContainer();
        $badRouter->boot()->registerRoutes(container: $container);

        // 3. Assertions: The expected behavior is that the router simply finds no routes
        // and its internal routes table remains empty. No exception should be thrown.
        static::assertEmpty($badRouter->routes, 'The routes array should be empty for a non-existent directory.');
        static::assertNotFalse($badRouter->boot(), 'The boot method should still return the router instance.');
    }
}
