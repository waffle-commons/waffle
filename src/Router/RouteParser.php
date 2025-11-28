<?php

declare(strict_types=1);

namespace Waffle\Router;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Waffle\Attribute\Route;
use Waffle\Commons\Contracts\Constant\Constant;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Utils\Trait\ReflectionTrait;

class RouteParser
{
    use ReflectionTrait;

    /**
     * @param class-string $controllerClass
     * @return array<array-key, array{
     * classname: class-string,
     * method: string,
     * arguments: array<string, mixed>,
     * path: string,
     * name: non-falsy-string
     * }>
     */
    public function parse(ContainerInterface $container, string $controllerClass): array
    {
        if (!class_exists($controllerClass)) {
            return [];
        }

        // 1. Analyze class via Reflection FIRST to avoid unnecessary/unsafe instantiation.
        $reflection = new ReflectionClass($controllerClass);

        // Ignore abstracts and interfaces
        if ($reflection->isAbstract() || $reflection->isInterface()) {
            return [];
        }

        // Check if the class has the #[Route] attribute.
        // If not, it's not a controller we care about, so don't instantiate it.
        $attributes = $reflection->getAttributes(Route::class);
        if (empty($attributes)) {
            return [];
        }

        // 2. Now that we know it's a valid route candidate, resolve it from container.
        if (!$container->has(id: $controllerClass)) {
            return [];
        }

        /** @var object $controller */
        $controller = $container->get(id: $controllerClass);
        $classRoute = $this->newAttributeInstance(
            className: $controller,
            attribute: Route::class,
        );

        if (!$classRoute instanceof Route) {
            return [];
        }

        $routes = [];
        foreach ($this->getMethods(object: $controller) as $method) {
            $newRoute = $this->createRoute($controllerClass, $classRoute, $method, $routes);
            if ($newRoute) {
                $routes[] = $newRoute;
            }
        }

        return $routes;
    }

    /**
     * @param class-string $file
     * @param array<array-key, array{
     * classname: class-string,
     * method: string,
     * arguments: array<string, mixed>,
     * path: string,
     * name: non-falsy-string
     * }> $routes
     * @return array{
     * classname: class-string,
     * method: string,
     * arguments: array<string, mixed>,
     * path: string,
     * name: non-falsy-string
     * }|null
     */
    private function createRoute(string $file, Route $classRoute, ReflectionMethod $method, array $routes): null|array
    {
        foreach ($method->getAttributes(Route::class) as $attribute) {
            $route = $attribute->newInstance();

            // --- Improved Path Concatenation ---
            $basePath = rtrim($classRoute->path, '/'); // Examples: '' (for '/'), '/admin'
            $methodPath = ltrim($route->path, '/'); // Examples: '', 'users', 'users/{id}'

            // If the method path is empty, use the base path directly. If base was '/', ensure path is '/'.
            if ($methodPath === '') {
                $path = $basePath === '' ? '/' : $basePath;
            }
            // If the base path is empty (was '/'), just use the method path prefixed with '/'
            elseif ($basePath === '') {
                $path = '/' . $methodPath;
            }
            // Otherwise, join them with a single slash
            else {
                $path = $basePath . '/' . $methodPath;
            }
            // --- End Improved Path Concatenation ---

            if (!$this->isRouteRegistered($path, $routes)) {
                return [
                    Constant::CLASSNAME => $file,
                    Constant::METHOD => $method->getName(),
                    Constant::ARGUMENTS => $this->extractParameters($method),
                    Constant::PATH => $path, // Use the cleaned path
                    Constant::NAME => ($classRoute->name ?? 'default') . '_' . ($route->name ?? 'default'),
                ];
            }
        }
        return null;
    }

    /**
     * @return array<string, mixed|string|null>
     */
    private function extractParameters(ReflectionMethod $method): array
    {
        $params = [];
        foreach ($method->getParameters() as $param) {
            if ($param->getType() instanceof ReflectionNamedType) {
                /** @var ReflectionNamedType $paramType */
                $paramType = $param->getType();
                $params[$param->getName()] = $paramType?->getName();
            }
        }
        return $params;
    }

    /**
     * @param string $path
     * @param array<array-key, array{
     * classname: class-string,
     * method: string,
     * arguments: array<string, mixed>,
     * path: string,
     * name: non-falsy-string
     * }> $routes
     * @return bool
     */
    private function isRouteRegistered(string $path, array $routes): bool
    {
        foreach ($routes as $route) {
            if ($route[Constant::PATH] === $path) {
                return true;
            }
        }
        return false;
    }
}
