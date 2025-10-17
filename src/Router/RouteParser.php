<?php

declare(strict_types=1);

namespace Waffle\Router;

use ReflectionMethod;
use ReflectionNamedType;
use Waffle\Attribute\Route;
use Waffle\Core\Constant;
use Waffle\Interface\ContainerInterface;
use Waffle\Trait\ReflectionTrait;

class RouteParser
{
    use ReflectionTrait;

    /**
     * @param class-string $controllerClass
     * @return array{}|non-empty-list<array{
     *      classname: class-string,
     *      method: string,
     *      arguments: array<string, mixed>,
     *      path: string,
     *      name: non-falsy-string
     *  }>
     */
    public function parse(ContainerInterface $container, string $controllerClass): array
    {
        if (!$container->has(id: $controllerClass)) {
            return [];
        }

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
     * @param array{}|non-empty-list<array{
     *      classname: class-string,
     *      method: string,
     *      arguments: array<string, mixed>,
     *      path: string,
     *      name: non-falsy-string
     *  }> $routes
     * @return array{
     *      classname: class-string,
     *      method: string,
     *      arguments: array<string, mixed>,
     *      path: string,
     *      name: non-falsy-string
     *  }|null
     */
    private function createRoute(string $file, Route $classRoute, ReflectionMethod $method, array $routes): null|array
    {
        foreach ($method->getAttributes(Route::class) as $attribute) {
            $route = $attribute->newInstance();
            $path = $classRoute->path . $route->path;

            if (!$this->isRouteRegistered($path, $routes)) {
                return [
                    Constant::CLASSNAME => $file,
                    Constant::METHOD => $method->getName(),
                    Constant::ARGUMENTS => $this->extractParameters($method),
                    Constant::PATH => $path,
                    Constant::NAME => ($classRoute->name ?? 'default') . '_' . ($route->name ?? 'default'),
                ];
            }
        }
        return null;
    }

    /**
     * @return array<string, string|null>
     */
    private function extractParameters(ReflectionMethod $method): array
    {
        $params = [];
        foreach ($method->getParameters() as $param) {
            if ($param->getType() instanceof ReflectionNamedType) {
                $params[$param->getName()] = $param->getType()?->getName();
            }
        }
        return $params;
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
        foreach ($routes as $route) {
            if ($route[Constant::PATH] === $path) {
                return true;
            }
        }
        return false;
    }
}
