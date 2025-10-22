<?php

declare(strict_types=1);

namespace Waffle\Router;

use Waffle\Interface\ContainerInterface;

class RouteDiscoverer
{
    private RouteParser $parser;

    /**
     * @var array<array-key, string>|false
     */
    private array|false $files;

    public function __construct(string|false $directory)
    {
        $this->parser = new RouteParser();
        $this->files = new ControllerFinder()->find(directory: $directory);
    }

    /**
     * @return array<array-key, array{
     *      classname: class-string,
     *      method: string,
     *      arguments: array<string, mixed>,
     *      path: string,
     *      name: non-falsy-string
     *  }>
     */
    public function discover(ContainerInterface $container): array
    {
        if (!$this->files) {
            return [];
        }

        $routes = [];
        foreach ($this->files as $file) {
            $discoveredRoutes = $this->parser->parse($container, $file);
            if ($discoveredRoutes !== []) {
                $routes = array_merge($routes, $discoveredRoutes);
            }
        }

        return $routes;
    }
}
