<?php

declare(strict_types=1);

namespace Waffle\Router;

use ReflectionNamedType;
use Waffle\Attribute\Route;
use Waffle\Core\Constant;
use Waffle\Core\Request;
use Waffle\Core\System;
use Waffle\Exception\SecurityException;
use Waffle\Interface\ContainerInterface;
use Waffle\Trait\ReflectionTrait;
use Waffle\Trait\RequestTrait;

final class Router
{
    use ReflectionTrait;
    use RequestTrait;

    private const string CACHE_FILE = 'waffle_routes_cache.php';

    private(set) string|false $directory {
        set => $this->directory = $value;
    }

    /**
     * @var array<array-key, string>|false
     */
    private(set) array|false $files {
        set => $this->files = $value;
    }

    /**
     * @var array{}|non-empty-list<array{
     *      classname: class-string,
     *      method: string,
     *      arguments: array<string, mixed>,
     *      path: string,
     *      name: non-falsy-string
     * }>
     */
    public array $routes {
        set => $this->routes = $value;
    }

    private(set) System $system;

    public function __construct(string|false $directory, System $system)
    {
        $this->routes = [];
        $this->directory = $directory;
        $this->files = false;
        $this->system = $system;
    }

    public function boot(): self
    {
        // 1. Critical Performance: Try to load from cache
        if ($this->loadRoutesFromCache()) {
            return $this;
        }

        $this->routes = [];
        $this->files = [];
        if (!$this->directory) {
            return $this;
        }

        $this->files = $this->scan(directory: $this->directory);

        return $this;
    }

    /**
     * @param string $directory
     * @return array<array-key, string>
     */
    protected function scan(string $directory): array
    {
        $files = [];

        // This prevents a fatal error if the configured controller directory is invalid.
        if (!is_dir($directory)) {
            return [];
        }

        $paths = scandir(directory: $directory);
        if ($paths) {
            foreach ($paths as $path) {
                if ($path === Constant::CURRENT_DIR || $path === Constant::PREVIOUS_DIR) {
                    continue;
                }
                $file = $directory . DIRECTORY_SEPARATOR . $path;
                if (is_dir(filename: $file)) {
                    // TODO(@supa-chayajin): Optimize `array_merge` method (maybe do it manually?)
                    $files = array_merge($files, $this->scan(directory: $file));
                }
                if (str_contains($path, Constant::PHPEXT)) {
                    $files[] = $this->className(path: $file);
                }
            }
        }

        return $files;
    }

    public function registerRoutes(null|ContainerInterface $container = null): self
    {
        if (null === $container || !$this->files) {
            return $this;
        }

        $routes = [];
        foreach ($this->files as $file) {
            if ($container->has($file)) {
                $controller = $container->get($file);
                $classRoute = $this->newAttributeInstance($controller, Route::class);

                if ($classRoute instanceof Route) {
                    foreach ($this->getMethods($controller) as $method) {
                        foreach ($method->getAttributes(Route::class) as $attribute) {
                            $route = $attribute->newInstance();
                            $path = $classRoute->path . $route->path;

                            if (!$this->isRouteRegistered($path, $routes)) {
                                $params = [];
                                foreach ($method->getParameters() as $param) {
                                    if ($param->getType() instanceof ReflectionNamedType) {
                                        $params[$param->getName()] = $param->getType()?->getName();
                                    }
                                }
                                $routes[] = [
                                    Constant::CLASSNAME => $file,
                                    Constant::METHOD => $method->getName(),
                                    Constant::ARGUMENTS => $params,
                                    Constant::PATH => $path,
                                    Constant::NAME =>
                                        ($classRoute->name ?? 'default') . '_' . ($route->name ?? 'default'),
                                ];
                            }
                        }
                    }
                }
            }
        }
        $this->routes = $routes;

        if ($this->isProduction()) {
            $this->cacheRoutes();
        }

        return $this;
    }

    /**
     * @param string $path
     * @param array{}|non-empty-list<array{
     *      classname: class-string,
     *      method: string,
     *      arguments: array<string, mixed>,
     *      path: string,
     *      name: non-falsy-string
     *  }> $routes
     * @return bool
     */
    private function isRouteRegistered(string $path, array $routes): bool
    {
        return array_any($routes, static fn(array $route): bool => $route[Constant::PATH] === $path);
    }

    /**
     * Matches the current request against a registered route path.
     *
     * @param Request $req
     * @param array{
     *     classname: class-string,
     *     method: string,
     *     arguments: array<string, mixed>,
     *     path: string,
     *     name: non-falsy-string
     *  } $route
     * @return bool
     * @throws SecurityException
     */
    public function match(ContainerInterface $container, Request $req, array $route): bool
    {
        $pathSegments = $this->getPathUri($route[Constant::PATH]);
        $serverUri = $req->server(
            key: Constant::REQUEST_URI,
            default: Constant::EMPTY_STRING,
        );
        $urlSegments = $this->getRequestUri(uri: $serverUri);

        if (count($pathSegments) !== count($urlSegments)) {
            return false;
        }

        foreach ($pathSegments as $i => $pathSegment) {
            if (str_starts_with($pathSegment, '{') && str_ends_with($pathSegment, '}')) {
                // This is a dynamic parameter, it's a match by default at this stage.
                continue;
            }

            if ($pathSegment !== $urlSegments[$i]) {
                return false;
            }
        }

        // Security check is done once a match is confirmed.
        if ($container->has($route[Constant::CLASSNAME])) {
            $controllerInstance = $container->get($route[Constant::CLASSNAME]);
            $this->system->security->analyze($controllerInstance);
        }

        return true;
    }

    /**
     * Attempts to load routes from the cache file.
     * * @return bool True if routes were loaded successfully from cache, false otherwise.
     */
    private function loadRoutesFromCache(): bool
    {
        if ($this->isProduction()) {
            $cacheFile = $this->getCacheFilePath();
            if (file_exists(filename: $cacheFile)) {
                // The cache file returns the routes array directly

                /**
                 * @var array{}|non-empty-list<array{
                 *      classname: class-string,
                 *      method: string,
                 *      arguments: array<string, mixed>,
                 *      path: string,
                 *      name: non-falsy-string
                 *   }> $routesArray
                 */
                $routesArray = require $cacheFile;
                $this->routes = $routesArray;
                return true;
            }
        }

        return false;
    }

    private function cacheRoutes(): void
    {
        $cacheFile = $this->getCacheFilePath();
        $content = '<?php return ' . var_export($this->routes, true) . ';';
        file_put_contents($cacheFile, $content, LOCK_EX);
    }

    private function isProduction(): bool
    {
        return getenv(Constant::APP_ENV) === Constant::ENV_DEFAULT;
    }

    private function getCacheFilePath(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::CACHE_FILE;
    }

    /**
     * @return array{}|non-empty-list<array{
     *       classname: class-string,
     *       method: string,
     *       arguments: array<string, mixed>,
     *       path: string,
     *       name: non-falsy-string
     *  }>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
