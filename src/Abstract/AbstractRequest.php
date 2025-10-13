<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Waffle\Core\Response;
use Waffle\Exception\RouteNotFoundException;
use Waffle\Interface\ContainerInterface;
use Waffle\Interface\RequestInterface;
use Waffle\Interface\ResponseInterface;
use Waffle\Trait\RequestTrait;

abstract class AbstractRequest implements RequestInterface
{
    use RequestTrait;

    /**
     * @var array<mixed>
     */
    public array $globals {
        get => $GLOBALS;
    }

    /**
     * @var array<mixed>
     */
    public array $server {
        get => $_SERVER;
    }

    /**
     * @var array<mixed>
     */
    public array $get {
        get => $_GET;
    }

    /**
     * @var array<mixed>
     */
    public array $post {
        get => $_POST;
    }

    /**
     * @var array<mixed>
     */
    public array $files {
        get => $_FILES;
    }

    /**
     * @var array<mixed>
     */
    public array $cookie {
        get => $_COOKIE;
    }

    /**
     * @var array<mixed>
     */
    public array $session {
        get => $_SESSION;
    }

    /**
     * @var array<mixed>
     */
    public array $request {
        get => $_REQUEST;
    }

    /**
     * @var array<mixed>
     */
    public array $env {
        get => $_ENV;
    }

    public bool $cli = false {
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

    abstract public function __construct(ContainerInterface $container, bool $cli);

    #[\Override]
    public function configure(ContainerInterface $container, bool $cli): void
    {
        $this->container = $container;
        $this->cli = $cli;
    }

    /**
     * @throws RouteNotFoundException
     */
    #[\Override]
    public function process(): ResponseInterface
    {
        if (null === $this->currentRoute && !$this->isCli()) {
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
        return $this->cli;
    }
}
