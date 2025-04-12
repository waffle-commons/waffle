<?php

declare(strict_types=1);

namespace Waffle\Router;

use Waffle\Attribute\Route;
use Waffle\Core\Constant;
use Waffle\Core\Request;
use Waffle\Trait\ReflectionTrait;
use Waffle\Trait\RequestTrait;
use ReflectionNamedType;

class Router
{
    use ReflectionTrait;
    use RequestTrait;

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

    public function __construct(
        string|false $directory
    ) {
        $this->directory = $directory;
        $this->files = false;
    }

    public function boot(): self
    {
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
                    $files = array_merge($files, $this->scan(directory: $file));
                } else {
                    if (str_contains(haystack: $path, needle: Constant::PHPEXT)) {
                        $files[] = $this->className(path: $file);
                    }
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
                                    if ($param->getType() instanceof ReflectionNamedType) {
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
        foreach ($routes as $route) {
            if ($route[Constant::PATH] === $path) {
                return true;
            }
        }

        return  false;
    }

    /**
     * @param Request $req
     * @param array{
     *     classname: string,
     *     method: non-empty-string,
     *     arguments: array<non-empty-string, string>,
     *     path: string,
     *     name: non-falsy-string
     *  } $route
     * @return bool
     */
    public function match(Request $req, array $route): bool
    {
        $path = $this->getPathUri(path: $route[Constant::PATH] ?: Constant::EMPTY_STRING);
        $url = $this->getRequestUri(uri: $req->server[Constant::REQUEST_URI]);
        if (count(value: $path) === count(value: $url)) {
            $argsCount = count(value: $route[Constant::ARGUMENTS] ?: []);
            for ($i = 0; $i < count(value: $path); $i++) {
                preg_match(
                    pattern: '/^\{(.*)}$/',
                    subject: $path[$i],
                    matches: $matches,
                    flags: PREG_UNMATCHED_AS_NULL
                );
                if (!empty($matches[0])) {
                    array_splice(array: $path, offset: $i, length: $argsCount);
                    array_splice(array: $url, offset: $i, length: $argsCount);
                }
            }
            /** @var bool[] $isMatch */
            $isMatch = [];
            for ($i = 0; $i < count(value: $url); $i++) {
                $isMatch[] = $path[$i] === $url[$i];
            }
            if (
                array_all($isMatch, function (bool $value) {
                    return $value === true;
                })
            ) {
                return true;
            }
        }

        return false;
    }
}
