<?php

declare(strict_types=1);

namespace Waffle\Interface;

use Waffle\Enum\AppMode;
use Waffle\Enum\HttpBag;
use Waffle\Http\ParameterBag;

interface RequestInterface
{
    public AppMode $cli {
        get;
        set;
    }

    /**
     * @var array{
     *     classname: string,
     *     method: non-empty-string,
     *     arguments: array<non-empty-string, string>,
     *     path: string,
     *     name: non-falsy-string
     * }|null
     */
    public null|array $currentRoute {
        get;
        set;
    }

    public null|ContainerInterface $container {
        get;
        set;
    }

    /**
     * @template T
     * @param ContainerInterface $container
     * @param AppMode $cli
     * @param array{
     *       server: T|string|array<mixed>,
     *       get: T|string|array<mixed>,
     *       post: T|string|array<mixed>,
     *       files: T|string|array<mixed>,
     *       cookie: T|string|array<mixed>,
     *       session: T|string|array<mixed>,
     *       request: T|string|array<mixed>,
     *       env: T|string|array<mixed>
     *   } $globals
     * @return void
     */
    public function configure(ContainerInterface $container, AppMode $cli, array $globals = []): void;

    public function process(): ResponseInterface;

    /**
     * @param array{
     *      classname: string,
     *      method: non-empty-string,
     *      arguments: array<non-empty-string, string>,
     *      path: string,
     *      name: non-falsy-string
     *  }|null $route
     * @return $this
     */
    public function setCurrentRoute(null|array $route = null): self;

    public function isCli(): bool;

    public function bag(HttpBag $key): ParameterBag;
}
