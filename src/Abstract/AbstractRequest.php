<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Waffle\Core\Response;
use Waffle\Enum\AppMode;
use Waffle\Exception\RouteNotFoundException;
use Waffle\Interface\ContainerInterface;
use Waffle\Interface\RequestInterface;
use Waffle\Interface\ResponseInterface;
use Waffle\Trait\RequestTrait;

abstract class AbstractRequest implements RequestInterface
{
    use RequestTrait;

    /**
     * @template T
     * @var T|string|array<mixed>
     */
    private array $server;

    /**
     * @template T
     * @var T|string|array<mixed>
     */
    private array $get;

    /**
     * @template T
     * @var T|string|array<mixed>
     */
    private array $post;

    /**
     * @template T
     * @var T|string|array<mixed>
     */
    private array $files;

    /**
     * @template T
     * @var T|string|array<mixed>
     */
    private array $cookie;

    /**
     * @template T
     * @var T|string|array<mixed>
     */
    private array $session;

    /**
     * @template T
     * @var T|string|array<mixed>
     */
    private array $request;

    /**
     * @template T
     * @var T|string|array<mixed>
     */
    private array $env;

    public AppMode $cli = AppMode::WEB {
        set => $this->cli = $value;
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
    public null|array $currentRoute = null {
        get => $this->currentRoute;
        set => $this->currentRoute = $value;
    }

    public null|ContainerInterface $container = null {
        set => $this->container = $value;
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
     */
    abstract public function __construct(ContainerInterface $container, AppMode $cli, array $globals = []);

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
    #[\Override]
    public function configure(ContainerInterface $container, AppMode $cli, array $globals = []): void
    {
        $this->container = $container;
        $this->cli = $cli;
        foreach ($globals as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @throws RouteNotFoundException
     */
    #[\Override]
    public function process(): ResponseInterface
    {
        if (null === $this->currentRoute && AppMode::WEB === $this->cli) {
            // Instead of exiting, we now throw a specific exception.
            throw new RouteNotFoundException();
        }

        return new Response(handler: $this);
    }

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
    #[\Override]
    public function setCurrentRoute(null|array $route = null): self
    {
        $this->currentRoute = $route;

        return $this;
    }

    public function isCli(): bool
    {
        return $this->cli === AppMode::CLI;
    }

    /**
     * @template T
     * @param string $key
     * @param T $default
     * @return T|string|array<mixed>
     */
    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * @template T
     * @param string $key
     * @param T $default
     * @return T|string|array<mixed>
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    /**
     * @template T
     * @param string $key
     * @param T $default
     * @return T|string|array<mixed>
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * @template T
     * @param string $key
     * @param T $default
     * @return T|string|array<mixed>
     */
    public function files(string $key, mixed $default = null): mixed
    {
        return $this->files[$key] ?? $default;
    }

    /**
     * @template T
     * @param string $key
     * @param T $default
     * @return T|string|array<mixed>
     */
    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookie[$key] ?? $default;
    }

    /**
     * @template T
     * @param string $key
     * @param T $default
     * @return T|string|array<mixed>
     */
    public function session(string $key, mixed $default = null): mixed
    {
        return $this->session[$key] ?? $default;
    }

    /**
     * @template T
     * @param string $key
     * @param T $default
     * @return T|string|array<mixed>
     */
    public function request(string $key, mixed $default = null): mixed
    {
        return $this->request[$key] ?? $default;
    }

    /**
     * @template T
     * @param string $key
     * @param T $default
     * @return T|string|array<mixed>
     */
    public function env(string $key, mixed $default = null): mixed
    {
        return $this->env[$key] ?? $default;
    }
}
