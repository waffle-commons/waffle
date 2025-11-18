<?php

declare(strict_types=1);

namespace WaffleTests\Router;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Waffle\Commons\Container\Container as CommonsContainer; // Import inner container
use Waffle\Commons\Http\ServerRequest;
use Waffle\Commons\Http\Uri;
use Waffle\Core\Config;
use Waffle\Core\Container;
use Waffle\Core\Security;
use Waffle\Core\System;
use Waffle\Interface\ContainerInterface;
use Waffle\Router\Router;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\Controller\TempController;

#[CoversClass(Router::class)]
final class RouterTest extends TestCase
{
    private Router $router;
    private array $serverBackup;
    private System $system;
    private ContainerInterface $container;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->serverBackup = $_SERVER;

        $testConfig = $this->createAndGetConfig(securityLevel: 2);
        $security = new Security($testConfig);

        // FIX: Instantiate inner container first
        $innerContainer = new CommonsContainer();
        $this->container = new Container($innerContainer, $security);

        $this->container->set(Config::class, $testConfig);
        $this->container->set(Security::class, $security);
        $this->container->set(TempController::class, TempController::class);

        $this->system = new System($security);
        $this->router = new Router(
            directory: 'tests/src/Helper/Controller',
            system: $this->system,
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        parent::tearDown();
    }

    public function testRegisterRoutesDiscoversAndBuildsRoutes(): void
    {
        $this->createTestConfigFile(securityLevel: 2);
        $container = $this->createRealContainer(level: 2);
        $this->router->boot(container: $container);

        static::assertNotEmpty($this->router->routes);
        static::assertCount(5, $this->router->routes);

        $foundRoute = false;
        foreach ($this->router->routes as $route) {
            if ('user_users_list' === $route['name']) {
                $foundRoute = true;
                static::assertSame(TempController::class, $route['classname']);
                static::assertSame('/users', $route['path']);
                static::assertSame('list', $route['method']);
                break;
            }
        }
        static::assertTrue($foundRoute);
    }

    public function testMatchWithStaticRoute(): void
    {
        $container = $this->createRealContainer(level: 2);
        $this->router->boot(container: $container);

        // Update: Use ServerRequest
        $uri = new Uri('/users');
        $request = new ServerRequest('GET', $uri);

        $matchingRoute = null;
        foreach ($this->router->routes as $route) {
            if ($this->router->match($container, $request, $route)) {
                $matchingRoute = $route;
                break;
            }
        }

        static::assertNotNull($matchingRoute, 'A matching route should have been found for /users.');
        static::assertSame('user_users_list', $matchingRoute['name']);
    }

    #[DataProvider('dynamicRouteProvider')]
    public function testMatchWithDynamicParameterRoutes(string $url, string $expectedRouteName): void
    {
        $container = $this->createRealContainer(level: 2);
        $this->router->boot(container: $container);

        // Update: Use ServerRequest
        $uri = new Uri($url);
        $request = new ServerRequest('GET', $uri);

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

    public static function dynamicRouteProvider(): array
    {
        return [
            'Single integer parameter' => ['/users/123', 'user_users_show'],
            'Multiple parameters (int and string)' => ['/users/42/john-doe-slug', 'user_users_details'],
        ];
    }

    public function testNoMatchForNonExistentRoute(): void
    {
        $container = $this->createRealContainer(level: 2);
        $this->router->boot(container: $container);

        // Update: Use ServerRequest
        $uri = new Uri('/non-existent-route');
        $request = new ServerRequest('GET', $uri);

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
        $this->router->boot(container: $container);
        static::assertFileExists($cacheFile, 'The router should have created a cache file.');

        /** @var array $cachedRoutes */
        $cachedRoutes = require $cacheFile;
        static::assertNotEmpty($cachedRoutes, 'The cache file should not be empty.');
        static::assertCount(5, $cachedRoutes, 'The cache file should contain the correct number of routes.');

        unlink($cacheFile);
        putenv('APP_ENV=test');
    }

    /**
     * This test validates the production optimization feature of route caching.
     */
    public function testBootLoadsRoutesFromCacheInProduction(): void
    {
        putenv('APP_ENV=prod');
        $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'waffle_routes_cache.php';
        $routes = $this->provideRoutesArray();
        $content = '<?php return ' . var_export($routes, true) . ';';
        file_put_contents($cacheFile, $content, LOCK_EX);

        $container = $this->createRealContainer(level: 2);
        $this->router->boot(container: $container);
        static::assertFileExists($cacheFile, 'The router should have created a cache file.');

        $cachedRoutes = $this->router->routes;
        static::assertSame($routes, $cachedRoutes, 'The cache file should have the same routes.');
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
        $badRouter->boot(container: $container);

        // 3. Assertions: The expected behavior is that the router simply finds no routes
        // and its internal routes table remains empty. No exception should be thrown.
        static::assertEmpty($badRouter->routes, 'The routes array should be empty for a non-existent directory.');
        static::assertNotFalse(
            $badRouter->boot(container: $container),
            'The boot method should still return the router instance.',
        );
    }

    private function provideRoutesArray(): array
    {
        return [
            [
                'classname' => 'WaffleTests\Helper\Controller\TempController',
                'method' => 'list',
                'arguments' => [],
                'path' => '/users',
                'name' => 'user_users_list',
            ],
            [
                'classname' => 'WaffleTests\Helper\Controller\TempController',
                'method' => 'show',
                'arguments' => [
                    'id' => 'int',
                ],
                'path' => '/users/{id}',
                'name' => 'user_users_show',
            ],
            [
                'classname' => 'WaffleTests\Helper\Controller\TempController',
                'method' => 'details',
                'arguments' => [
                    'id' => 'int',
                    'slug' => 'string',
                ],
                'path' => '/users/{id}/{slug}',
                'name' => 'user_users_details',
            ],
            [
                'classname' => 'WaffleTests\Helper\Controller\TempController',
                'method' => 'profile',
                'arguments' => [],
                'path' => '/users/profile/view',
                'name' => 'user_users_profile_view',
            ],
            [
                'classname' => 'WaffleTests\Helper\Controller\TempController',
                'method' => 'throwError',
                'arguments' => [],
                'path' => '/trigger-error',
                'name' => 'user_trigger_error',
            ],
        ];
    }
}
