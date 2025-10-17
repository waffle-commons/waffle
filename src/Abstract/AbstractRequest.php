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

    /** @var array<mixed> */
    public readonly array $server;

    /** @var array<mixed> */
    public readonly array $get;

    /** @var array<mixed> */
    public readonly array $post;

    /** @var array<mixed> */
    public readonly array $files;

    /** @var array<mixed> */
    public readonly array $cookie;

    /** @var array<mixed> */
    public readonly array $session;

    /** @var array<mixed> */
    public readonly array $request;

    /** @var array<mixed> */
    public readonly array $env;

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
     * @param ContainerInterface $container
     * @param AppMode $cli
     * @param array{
     *       server: array<mixed>,
     *       get: array<mixed>,
     *       post: array<mixed>,
     *       files: array<mixed>,
     *       cookie: array<mixed>,
     *       session: array<mixed>,
     *       request: array<mixed>,
     *       env: array<mixed>
     *   } $globals
     */
    abstract public function __construct(ContainerInterface $container, AppMode $cli, array $globals = []);

    /**
     * @param ContainerInterface $container
     * @param AppMode $cli
     * @param array{
     *       server: array<mixed>,
     *       get: array<mixed>,
     *       post: array<mixed>,
     *       files: array<mixed>,
     *       cookie: array<mixed>,
     *       session: array<mixed>,
     *       request: array<mixed>,
     *       env: array<mixed>
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
}
