<?php

declare(strict_types=1);

namespace Waffle\Router;

use Waffle\Attribute\Route;
use Waffle\Core\Constant;
use Waffle\Core\Request;
use Waffle\Core\System;
use Waffle\Exception\SecurityException;
use Waffle\Trait\ReflectionTrait;
use Waffle\Trait\RequestTrait;

final class Router
{
    use ReflectionTrait;
    use RequestTrait;

    private const string CACHE_FILE = 'waffle_routes_cache.php';

    private(set) string | false $directory
        {
            set => $this->directory = $value;
        }

    /**
     * @var array<string, string>|false
     */
    private(set) array | false $files
        {
            set => $this->files = $value;
        }

    /**
     * @var list<array{
     *     classname: string,
     *     method: non-empty-string,
     *     arguments: array<non-empty-string, string>,
     *     path: string,
     *     name: non-falsy-string
     * }>
     */
    private(set) array $routes
        {
            set => $this->routes = $value;
        }

    private(set) System $system;

    public function __construct(
        string|false $directory,
        System $system,
    ) {
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

        $this->routes = $this->files = [];
        if ($this->directory === false) {
            return $this;
        }

        $this->files = $this->scan(directory: $this->directory);

        return $this;
    }

    /**
     * @param string $directory
     * @return string[]
     */
    protected function scan(string $directory): array
    {
        $files = [];
        $paths = scandir(directory: $directory);
        if ($paths !== false) {
            foreach ($paths as $path) {
                if ($path === Constant::CURRENT_DIR || $path === Constant::PREVIOUS_DIR) {
                    continue;
                }
                $file = $directory . DIRECTORY_SEPARATOR . $path;
                if (is_dir(filename: $file)) {
                    // TODO: Optimize `array_merge` method (maybe do it manually?)
                    $files = array_merge($files, $this->scan(directory: $file));
                } elseif (str_contains(haystack: $path, needle: Constant::PHPEXT)) {
                    $files[] = $this->className(path: $file);
                }
            }
        }

        return $files;
    }

    public function registerRoutes(): self
    {
        $routes = [];
        if ($this->files !== false) {
            foreach ($this->files as $file) {
                $controller = new $file();
                $classRoute = $this->newAttributeInstance(className: $controller, attribute: Route::class);
                if ($classRoute instanceof Route) {
                    $methods = $this->getMethods(className: $controller);
                    foreach ($methods as $method) {
                        $attributes = $method->getAttributes(name: Route::class);
                        foreach ($attributes as $attribute) {
                            $route = $attribute->newInstance();
                            $path = $classRoute->path . $route->path;
                            if (!$this->isRouteRegistered(path: $path, routes: $routes)) {
                                $classRouteName = $classRoute->name ?: Constant::DEFAULT;
                                $routeName = $route->name ?: Constant::DEFAULT;
                                $params = [];
                                foreach ($method->getParameters() as $param) {
                                    // Uses Reflection to get parameter types for argument resolution
                                    if ($param->getType() instanceof \ReflectionNamedType) {
                                        $params[$param->getName()] = $param->getType()->getName();
                                    }
                                }
                                $routes[] = [
                                    Constant::CLASSNAME => $file,
                                    Constant::METHOD => $method->getName(),
                                    Constant::ARGUMENTS => $params,
                                    Constant::PATH => $path,
                                    Constant::NAME => $classRouteName .  '_' . $routeName,
                                ];
                            }
                        }
                    }
                }
            }
        }
        $this->routes = $routes;

        // 2. Critical Performance: Writes routes to cache if in production mode
        if ($this->isProduction()) {
            $cacheFile = $this->getCacheFilePath();
            // Uses var_export to generate a readable and fast-loading PHP array
            $content = '<?php return ' . var_export($this->routes, true) . ';';

            // Writes content to file. Uses @ to suppress errors in case of permission issues,
            // although a real framework should handle permissions and cache directory creation.
            @file_put_contents(filename: $cacheFile, data: $content, flags: LOCK_EX);
        }

        return $this;
    }

    /**
     * @param string $path
     * @param list<array{
     *      classname: string,
     *      method: non-empty-string,
     *      arguments: array<non-empty-string, string>,
     *      path: string,
     *      name: non-falsy-string
     *  }> $routes
     * @return bool
     */
    private function isRouteRegistered(string $path, array $routes): bool
    {
        return array_any($routes, static fn($route) => $route[Constant::PATH] === $path);
    }

    /**
     * Matches the current request against a registered route path.
     *
     * @param Request $req
     * @param array{
     *     classname: string,
     *     method: non-empty-string,
     *     arguments: array<non-empty-string, string>,
     *     path: string,
     *     name: non-falsy-string
     *  } $route
     * @return bool
     * @throws SecurityException
     */
    public function match(Request $req, array $route): bool
    {
        $pathSegments = $this->getPathUri(path: $route[Constant::PATH] ?: Constant::EMPTY_STRING);
        $urlSegments = $this->getRequestUri(uri: $req->server[Constant::REQUEST_URI]);

        // 1. Path length must match exactly.
        $iMax = count(value: $pathSegments);
        if ($iMax !== count(value: $urlSegments)) {
            return false;
        }

        $isMatch = true;

        // 2. Iterate and compare each segment.
        for ($i = 0; $i < $iMax; $i++) {
            $pathSegment = $pathSegments[$i];
            $urlSegment = $urlSegments[$i];

            // Check if the segment in the route definition is a dynamic parameter {name}.
            preg_match(
                pattern: '/^\{(.*)}$/',
                subject: $pathSegment,
                matches: $matches,
                flags: PREG_UNMATCHED_AS_NULL
            );

            if (!empty($matches[0])) {
                // If it is a parameter, it matches unconditionally (e.g., /{id} matches /123).

                // --- SECURITY INJECTION POINT ---
                // We use the System's Security service to analyze the controller class
                // immediately after a match is found. This prevents the execution
                // of potentially insecure classes/methods.
                if (class_exists(class: $route[Constant::CLASSNAME])) {
                    $controllerInstance = new $route[Constant::CLASSNAME]();
                    // We call analyze on the controller to validate its security level
                    $this->system->security->analyze(object: $controllerInstance);
                }
                // --- END SECURITY INJECTION ---

                continue;
            }

            // If it is NOT a parameter, the segments must be an exact match.
            if ($pathSegment !== $urlSegment) {
                $isMatch = false;
                break;
            }
        }

        return $isMatch;
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
                $this->routes = require $cacheFile;
                return true;
            }
        }

        return false;
    }

    private function isProduction(): bool
    {
        return (getenv(name: Constant::APP_ENV) === Constant::ENV_DEFAULT);
    }

    private function getCacheFilePath(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::CACHE_FILE;
    }
}
