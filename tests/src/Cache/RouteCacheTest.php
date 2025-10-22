<?php

declare(strict_types=1);

namespace WaffleTests\Cache;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Cache\RouteCache;
use Waffle\Core\Constant;
use WaffleTests\AbstractTestCase;

#[CoversClass(RouteCache::class)]
final class RouteCacheTest extends AbstractTestCase
{
    private RouteCache $routeCache;
    private string $cacheFilePath;
    private mixed $originalAppEnv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->routeCache = new RouteCache();

        // Use reflection to get the private cache file path for testing
        $reflector = new \ReflectionClass(RouteCache::class);
        $method = $reflector->getMethod('getCacheFilePath');
        // $method->setAccessible(true); // No longer needed in PHP 8.1+ for private methods if called from same scope/instance
        $this->cacheFilePath = $method->invoke($this->routeCache); // Call on instance

        // Clean up any potential leftover cache file
        if (file_exists($this->cacheFilePath)) {
            unlink($this->cacheFilePath);
        }

        // Backup original APP_ENV
        $this->originalAppEnv = getenv(Constant::APP_ENV);
    }

    protected function tearDown(): void
    {
        // Clean up cache file after each test
        if (file_exists($this->cacheFilePath)) {
            unlink($this->cacheFilePath);
        }
        // Restore original APP_ENV
        if ($this->originalAppEnv === false) {
            putenv(Constant::APP_ENV); // Unset if it wasn't set before
        } else {
            putenv(Constant::APP_ENV . '=' . $this->originalAppEnv);
        }
        parent::tearDown();
    }

    public function testLoadReturnsNullWhenNotInProduction(): void
    {
        // Arrange
        putenv(Constant::APP_ENV . '=' . Constant::ENV_DEV);
        // Create a dummy cache file to ensure it's ignored
        file_put_contents($this->cacheFilePath, '<?php return ["dummy_route"];');

        // Act
        $result = $this->routeCache->load();

        // Assert
        $this->assertNull($result);
    }

    public function testLoadReturnsNullWhenInProductionButCacheFileDoesNotExist(): void
    {
        // Arrange
        putenv(Constant::APP_ENV . '=' . Constant::ENV_PROD);
        // Ensure cache file does not exist
        if (file_exists($this->cacheFilePath)) {
            unlink($this->cacheFilePath);
        }

        // Act
        $result = $this->routeCache->load();

        // Assert
        $this->assertNull($result);
    }

    public function testLoadReturnsRoutesWhenInProductionAndCacheExists(): void
    {
        // Arrange
        putenv(Constant::APP_ENV . '=' . Constant::ENV_PROD);
        $expectedRoutes = [
            ['path' => '/', 'controller' => 'HomeController', 'name' => 'home'],
            ['path' => '/about', 'controller' => 'AboutController', 'name' => 'about'],
        ];
        $content = '<?php return ' . var_export($expectedRoutes, true) . ';';
        file_put_contents($this->cacheFilePath, $content);

        // Act
        $result = $this->routeCache->load();

        // Assert
        $this->assertSame($expectedRoutes, $result);
    }

    public function testSaveDoesNothingWhenNotInProduction(): void
    {
        // Arrange
        putenv(Constant::APP_ENV . '=' . Constant::ENV_DEV);
        $routesToSave = [['path' => '/test', 'name' => 'test']];

        // Act
        $this->routeCache->save($routesToSave);

        // Assert
        $this->assertFileDoesNotExist($this->cacheFilePath);
    }

    public function testSaveWritesCacheFileWhenInProduction(): void
    {
        // Arrange
        putenv(Constant::APP_ENV . '=' . Constant::ENV_PROD);
        $routesToSave = [
            ['path' => '/save-test', 'controller' => 'SaveController', 'name' => 'save_test'],
        ];

        // Act
        $this->routeCache->save($routesToSave);

        // Assert
        $this->assertFileExists($this->cacheFilePath);
        /** @var array<array<string, string>> $loadedRoutes */
        $loadedRoutes = require $this->cacheFilePath;
        $this->assertSame($routesToSave, $loadedRoutes);
    }

    public function testIsProductionHelper(): void
    {
        // Use reflection to test the private helper method
        $reflector = new \ReflectionClass(RouteCache::class);
        $method = $reflector->getMethod('isProduction');
        // $method->setAccessible(true); // Not needed PHP 8.1+

        putenv(Constant::APP_ENV . '=' . Constant::ENV_PROD);
        $this->assertTrue($method->invoke($this->routeCache), 'isProduction should be true for prod env');

        putenv(Constant::APP_ENV . '=' . Constant::ENV_DEV);
        $this->assertFalse($method->invoke($this->routeCache), 'isProduction should be false for dev env');

        putenv(Constant::APP_ENV . '=' . Constant::ENV_TEST);
        $this->assertFalse($method->invoke($this->routeCache), 'isProduction should be false for test env');

        // Test default case (should be prod if not set)
        putenv(Constant::APP_ENV); // Unset
        $this->assertTrue($method->invoke($this->routeCache), 'isProduction should default to true if env var is not set');

    }
}
