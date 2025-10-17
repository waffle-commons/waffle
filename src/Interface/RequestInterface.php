<?php

declare(strict_types=1);

namespace Waffle\Interface;

use Waffle\Enum\AppMode;

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

    /**
     * @template T
     * @param string $key
     * @param T $default
     * @return T|string|array<mixed>
     */
    public function server(string $key, mixed $default = null): mixed;

    /**
     * @template T
     * @param string $key
     * @param T $default
     * @return T|string|array<mixed>
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * @template T
     * @param string $key
     * @param T $default
     * @return T|string|array<mixed>
     */
    public function post(string $key, mixed $default = null): mixed;

    /**
     * @template T
     * @param string $key
     * @param T $default
     * @return T|string|array<mixed>
     */
    public function files(string $key, mixed $default = null): mixed;

    /**
     * @template T
     * @param string $key
     * @param T $default
     * @return T|string|array<mixed>
     */
    public function cookie(string $key, mixed $default = null): mixed;

    /**
     * @template T
     * @param string $key
     * @param T $default
     * @return T|string|array<mixed>
     */
    public function session(string $key, mixed $default = null): mixed;

    /**
     * @template T
     * @param string $key
     * @param T $default
     * @return T|string|array<mixed>
     */
    public function request(string $key, mixed $default = null): mixed;

    /**
     * @template T
     * @param string $key
     * @param T $default
     * @return T|string|array<mixed>
     */
    public function env(string $key, mixed $default = null): mixed;
}
