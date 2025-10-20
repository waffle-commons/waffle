<?php

declare(strict_types=1);

namespace Waffle\Router;

use Waffle\Cache\RouteCache;
use Waffle\Core\Constant;
use Waffle\Core\Request;
use Waffle\Core\System;
use Waffle\Enum\HttpBag;
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
             * @var array{}|non-empty-list<array{
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
        $server = $req->bag(key: HttpBag::SERVER);
        $serverUri = $server->get(
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
        if ($container->has(id: $route[Constant::CLASSNAME])) {
            $controllerInstance = $container->get(id: $route[Constant::CLASSNAME]);
            $this->system->security->analyze($controllerInstance);
        }

        return true;
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
