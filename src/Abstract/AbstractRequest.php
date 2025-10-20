<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Waffle\Core\Response;
use Waffle\Enum\AppMode;
use Waffle\Enum\HttpBag;
use Waffle\Exception\RouteNotFoundException;
use Waffle\Http\ParameterBag;
use Waffle\Interface\ContainerInterface;
use Waffle\Interface\RequestInterface;
use Waffle\Interface\ResponseInterface;
use Waffle\Trait\RequestTrait;

abstract class AbstractRequest implements RequestInterface
{
    use RequestTrait;

    public ParameterBag $query; // For $_GET
    public ParameterBag $request; // For $_POST
    public ParameterBag $server; // For $_SERVER
    public ParameterBag $files; // For $_FILES
    public ParameterBag $cookies; // For $_COOKIE
    public ParameterBag $session; // For $_SESSION
    public ParameterBag $env; // For $_ENV

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
        $this->query = new ParameterBag(parameters: $globals['get'] ?? []);
        $this->request = new ParameterBag(parameters: $globals['post'] ?? []);
        $this->server = new ParameterBag(parameters: $globals['server'] ?? []);
        $this->files = new ParameterBag(parameters: $globals['files'] ?? []);
        $this->cookies = new ParameterBag(parameters: $globals['cookie'] ?? []);
        $this->session = new ParameterBag(parameters: $globals['session'] ?? []);
        $this->env = new ParameterBag(parameters: $globals['env'] ?? []);
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

    #[\Override]
    public function isCli(): bool
    {
        return $this->cli === AppMode::CLI;
    }

    #[\Override]
    public function bag(HttpBag $key): ParameterBag
    {
        return match ($key) {
            HttpBag::QUERY => $this->query,
            HttpBag::REQUEST => $this->request,
            HttpBag::SERVER => $this->server,
            HttpBag::FILES => $this->files,
            HttpBag::COOKIES => $this->cookies,
            HttpBag::SESSION => $this->session,
            HttpBag::ENV => $this->env,
        };
    }
}
