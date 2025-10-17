<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Waffle\Core\Response;
use Waffle\Enum\AppMode;
use Waffle\Interface\CliInterface;
use Waffle\Interface\ContainerInterface;
use Waffle\Interface\ResponseInterface;

abstract class AbstractCli implements CliInterface
{
    /**
     * @template T
     * @var T|string|array<mixed>
     */
    private array $server;

    /**
     * @template T
     * @var T|string|array<mixed>
     */
    private array $env;

    public AppMode $cli = AppMode::CLI {
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

    #[\Override]
    public function process(): ResponseInterface
    {
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
    public function env(string $key, mixed $default = null): mixed
    {
        return $this->env[$key] ?? $default;
    }
}
