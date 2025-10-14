<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Throwable;
use Waffle\Attribute\Configuration;
use Waffle\Core\Cli;
use Waffle\Core\Container;
use Waffle\Core\Request;
use Waffle\Core\Response;
use Waffle\Core\Security;
use Waffle\Core\System;
use Waffle\Core\View;
use Waffle\Exception\RouteNotFoundException;
use Waffle\Exception\SecurityException;
use Waffle\Factory\ContainerFactory;
use Waffle\Interface\CliInterface;
use Waffle\Interface\ContainerInterface;
use Waffle\Interface\KernelInterface;
use Waffle\Interface\RequestInterface;
use Waffle\Trait\DotenvTrait;
use Waffle\Trait\MicrokernelTrait;
use Waffle\Trait\ReflectionTrait;

abstract class AbstractKernel implements KernelInterface
{
    use MicrokernelTrait;
    use DotenvTrait;
    use ReflectionTrait;

    public object $config {
        get => $this->config;
        set => $this->config = $value;
    }

    protected(set) null|System $system = null {
        get => $this->system;
        set => $this->system = $value;
    }

    public null|ContainerInterface $container = null {
        get => $this->container;
        set => $this->container = $value;
    }

    public function handle(): void
    {
        try {
            $this->boot()->configure()->loadEnv();

            $handler = $this->isCli() ? $this->createCliFromRequest() : $this->createRequestFromGlobals();

            $this->run(handler: $handler);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    #[\Override]
    public function boot(): self
    {
        $this->config = new Configuration();

        return $this;
    }

    #[\Override]
    public function configure(): self
    {
        $this->config = $this->newAttributeInstance(
            className: $this->config,
            attribute: Configuration::class,
        );
        /** @var Configuration $config */
        $config = $this->config;

        $security = new Security(cfg: $this->config);

        $this->container = new Container(security: $security);

        $containerFactory = new ContainerFactory();
        $containerFactory->create(
            container: $this->container,
            directory: $config->serviceDir,
        );
        $containerFactory->create(
            container: $this->container,
            directory: $config->controllerDir,
        );

        $this->system = new System(security: $security)->boot(kernel: $this);

        return $this;
    }

    /**
     * @throws SecurityException
     */
    #[\Override]
    public function createRequestFromGlobals(): RequestInterface
    {
        $req = new Request(container: $this->container);
        if ($this->system instanceof System) {
            $router = $this->system->getRouter();
            if (null !== $router && !$req->isCli()) {
                $routes = $router->getRoutes();
                /**
                 * @var array{
                 *      classname: string,
                 *      method: non-empty-string,
                 *      arguments: array<non-empty-string, string>,
                 *      path: string,
                 *      name: non-falsy-string
                 *  } $route
                 */
                foreach ($routes as $route) {
                    if ($router->match(
                        container: $this->container,
                        req: $req,
                        route: $route,
                    )) {
                        $req->setCurrentRoute(route: $route);
                        break; // Stop after the first match
                    }
                }
            }
        }

        return $req;
    }

    #[\Override]
    public function createCliFromRequest(): CliInterface
    {
        // TODO(@supa-chayajin): Handle CLI command from request

        return new Cli(
            container: $this->container,
            cli: false,
        );
    }

    #[\Override]
    public function run(CliInterface|RequestInterface $handler): void
    {
        $handler->process()->render();
    }

    private function handleException(Throwable $e): void
    {
        $config = new Configuration();
        $security = new Security(cfg: $config);
        $this->container = new Container($security);
        $handler = $this->isCli() ? new Cli(container: $this->container) : new Request(container: $this->container);
        $statusCode = 500;
        $data = [
            'message' => 'An unexpected error occurred.',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        if ($e instanceof RouteNotFoundException) {
            $statusCode = 404;
            $data = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }

        http_response_code($statusCode);
        new Response(handler: $handler)->throw(view: new View(data: $data));
    }
}
