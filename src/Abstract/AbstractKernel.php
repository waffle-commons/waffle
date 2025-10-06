<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Throwable;
use Waffle\Attribute\Configuration;
use Waffle\Core\Cli;
use Waffle\Core\Request;
use Waffle\Core\Response;
use Waffle\Core\Security;
use Waffle\Core\System;
use Waffle\Core\View;
use Waffle\Exception\RouteNotFoundException;
use Waffle\Exception\SecurityException;
use Waffle\Interface\CliInterface;
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

    protected(set) object $config {
        set => $this->config = $value;
    }

    protected(set) null|System $system = null {
        set => $this->system = $value;
    }

    public function handle(): void
    {
        try {
            $this->boot();
            $this->configure();
            $this->loadEnv();

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

        $this->system = new System(security: new Security(cfg: $this->config))->boot(kernel: $this);

        return $this;
    }

    /**
     * @throws SecurityException
     */
    #[\Override]
    public function createRequestFromGlobals(): RequestInterface
    {
        $req = new Request(); // Removed setCurrentRoute() from here
        if ($this->system instanceof System) {
            $router = $this->system->getRouter();
            if (null !== $router && $req->isCli() === false) {
                $routes = $router->getRoutes();
                foreach ($routes as $route) {
                    if (
                        $router->match(
                            req: $req,
                            route: $route,
                        )
                    ) {
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
        // TODO: Handle CLI command from request

        return new Cli(cli: false)->setCurrentRoute();
    }

    #[\Override]
    public function run(CliInterface|RequestInterface $handler): void
    {
        $handler->process()->render();
    }

    private function handleException(Throwable $e): void
    {
        $handler = $this->isCli() ? new Cli() : new Request();
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
