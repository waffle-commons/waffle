<?php

declare(strict_types=1);

namespace Waffle\Router;

use Psr\Http\Message\ServerRequestInterface; // Import PSR-7
use Waffle\Cache\RouteCache;
use Waffle\Commons\Contracts\Routing\RouterInterface;
use Waffle\Commons\Contracts\Constant\Constant;
use Waffle\Core\System;
use Waffle\Exception\SecurityException;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Trait\ReflectionTrait;
use Waffle\Trait\RequestTrait; // Note: RequestTrait might also need updates or removal if it relies on old logic

final class Router implements RouterInterface
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
     * @var array<array-key, array{
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

    private readonly RouteCache $cache;

    private readonly RouteDiscoverer $discoverer;

    public function __construct(
        string|false $directory,
        private readonly System $system,
    ) {
        $this->routes = [];
        $this->files = false;
        $this->cache = new RouteCache();
        $this->discoverer = new RouteDiscoverer(directory: $directory);
    }

    public function boot(ContainerInterface $container): self
    {
        $cachedRoutes = $this->cache->load();
        if (null !== $cachedRoutes) {
            /**
             * @var array<array-key, array{
             *      classname: class-string,
             *      method: string,
             *      arguments: array<string, mixed>,
             *      path: string,
             *      name: non-falsy-string
             *  }> $routesArray
             */
            $routesArray = $cachedRoutes;
            $this->routes = $routesArray;

            return $this;
        }

        $this->routes = $this->discoverer->discover($container);
        $this->cache->save($this->routes);

        return $this;
    }

    /**
     * Matches the current request against a registered route path.
     *
     * @param ContainerInterface $container
     * @param ServerRequestInterface $req  <-- Update type hint
     * @param array{
     *        classname: class-string,
     *        method: string,
     *        arguments: array<string, mixed>,
     *        path: string,
     *        name: non-falsy-string
     *   } $route
     * @return bool
     * @throws SecurityException
     */
    public function match(ContainerInterface $container, ServerRequestInterface $req, array $route): bool
    {
        // Update logic to use PSR-7 methods
        // $req->getUri()->getPath() instead of RequestTrait helper

        // If using internal helper getPathUri from RequestTrait:
        $pathSegments = $this->getPathUri($route[Constant::PATH]);

        // PSR-7 URI Handling
        $uriPath = $req->getUri()->getPath();
        $urlSegments = $this->getPathUri($uriPath); // Reuse getPathUri for segments

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
        if ($container->has(id: $route[Constant::CLASSNAME])) {
            /** @var object $controllerInstance */
            $controllerInstance = $container->get(id: $route[Constant::CLASSNAME]);
            $this->system->security->analyze($controllerInstance);
        }

        return true;
    }

    /**
     * @return array<array-key, array{
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
