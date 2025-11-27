<?php

declare(strict_types=1);

namespace WaffleTests\Router;

use PHPUnit\Framework\Attributes\CoversClass;use PHPUnit\Framework\Attributes\DataProvider;use Psr\Http\Message\ServerRequestInterface;use Psr\Http\Message\UriInterface;use Waffle\Commons\Config\Config;use Waffle\Commons\Contracts\Container\ContainerInterface;use Waffle\Core\Container;use Waffle\Core\Security;use Waffle\Core\System;use Waffle\Router\Router;use WaffleTests\AbstractTestCase as TestCase;use WaffleTests\Helper\Controller\TempController;use WaffleTests\Helper\MockContainer;

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

        // Use MockContainer for testing
        $innerContainer = new MockContainer();
        $this->container = new Container($innerContainer, $security);
        $this->container->set(Config::class, $testConfig);
        $this->container->set(Security::class, $security);
        $this->container->set(TempController::class, new TempController());

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

    // ... (testRegisterRoutesDiscoversAndBuildsRoutes remains unchanged) ...
    public function testRegisterRoutesDiscoversAndBuildsRoutes(): void
    {
        $this->createTestConfigFile(securityLevel: 2);
        // We need a container for boot
        $this->router->boot(container: $this->container);

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
        $this->router->boot(container: $this->container);

        // Use Mocks for Request/Uri
        $uriMock = $this->createMock(UriInterface::class);
        $uriMock->method('getPath')->willReturn('/users');

        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getUri')->willReturn($uriMock);

        $matchingRoute = null;
        foreach ($this->router->routes as $route) {
            if ($this->router->match($this->container, $requestMock, $route)) {
                $matchingRoute = $route;
                break;
            }
        }

        static::assertNotNull($matchingRoute);
        static::assertSame('user_users_list', $matchingRoute['name']);
    }

    #[DataProvider('dynamicRouteProvider')]
    public function testMatchWithDynamicParameterRoutes(string $url, string $expectedRouteName): void
    {
        $this->router->boot(container: $this->container);

        $uriMock = $this->createMock(UriInterface::class);
        $uriMock->method('getPath')->willReturn($url);

        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getUri')->willReturn($uriMock);

        $matchingRoute = null;
        foreach ($this->router->routes as $route) {
            if ($this->router->match($this->container, $requestMock, $route)) {
                $matchingRoute = $route;
                break;
            }
        }

        static::assertNotNull($matchingRoute);
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
        $this->router->boot(container: $this->container);

        $uriMock = $this->createMock(UriInterface::class);
        $uriMock->method('getPath')->willReturn('/non-existent-route');

        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getUri')->willReturn($uriMock);

        $matchingRoute = null;
        foreach ($this->router->routes as $route) {
            if ($this->router->match($this->container, $requestMock, $route)) {
                $matchingRoute = $route;
                break;
            }
        }

        static::assertNull($matchingRoute);
    }

    // ... (Rest of tests unchanged) ...
    public function testRouteCachingInProductionEnvironment(): void
    {
        putenv('APP_ENV=prod');
        $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'waffle_routes_cache.php';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }

        $this->router->boot(container: $this->container);
        static::assertFileExists($cacheFile);

        $cachedRoutes = require $cacheFile;
        static::assertNotEmpty($cachedRoutes);
        static::assertCount(5, $cachedRoutes);

        unlink($cacheFile);
        putenv('APP_ENV=test');
    }

    public function testBootLoadsRoutesFromCacheInProduction(): void
    {
        putenv('APP_ENV=prod');
        $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'waffle_routes_cache.php';
        $routes = $this->provideRoutesArray();
        $content = '<?php return ' . var_export($routes, true) . ';';
        file_put_contents($cacheFile, $content, LOCK_EX);

        $this->router->boot(container: $this->container);
        static::assertFileExists($cacheFile);

        $cachedRoutes = $this->router->routes;
        static::assertSame($routes, $cachedRoutes);

        unlink($cacheFile);
        putenv('APP_ENV=test');
    }

    public function testRouterHandlesNonExistentDirectoryGracefully(): void
    {
        $badRouter = new Router(
            directory: __DIR__ . '/NonExistentDirectory',
            system: $this->system,
        );

        $badRouter->boot(container: $this->container);

        static::assertEmpty($badRouter->routes);
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
