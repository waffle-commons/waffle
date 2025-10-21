<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Waffle\Core\Response;
use Waffle\Enum\AppMode;
use Waffle\Enum\HttpBag;
use Waffle\Exception\Container\NotFoundException;
use Waffle\Http\ParameterBag;
use Waffle\Interface\CliInterface;
use Waffle\Interface\ContainerInterface;
use Waffle\Interface\ResponseInterface;

abstract class AbstractCli implements CliInterface
{
    public ParameterBag $server; // For $_SERVER
    public ParameterBag $env; // For $_ENV

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
     *       server: T|string|array<string, mixed>,
     *       env: T|string|array<string, mixed>
     *   } $globals
     */
    abstract public function __construct(ContainerInterface $container, AppMode $cli, array $globals = []);

    /**
     * @template T
     * @param ContainerInterface $container
     * @param AppMode $cli
     * @param array{
     *       server: T|string|array<string, mixed>,
     *       env: T|string|array<string, mixed>
     *   } $globals
     * @return void
     */
    #[\Override]
    public function configure(ContainerInterface $container, AppMode $cli, array $globals = []): void
    {
        $this->container = $container;
        $this->cli = $cli;
        /** @var array<string, mixed> $serverGlobals */
        $serverGlobals = $globals['server'] ?? [];
        $this->server = new ParameterBag(parameters: $serverGlobals);
        /** @var array<string, mixed> $envGlobals */
        $envGlobals = $globals['env'] ?? [];
        $this->env = new ParameterBag(parameters: $envGlobals);
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

    #[\Override]
    public function isCli(): bool
    {
        return $this->cli === AppMode::CLI;
    }

    /**
     * @throws NotFoundException
     */
    #[\Override]
    public function bag(HttpBag $key): ParameterBag
    {
        return match ($key) {
            HttpBag::SERVER => $this->server,
            HttpBag::ENV => $this->env,
            default => throw new NotFoundException(),
        };
    }
}
