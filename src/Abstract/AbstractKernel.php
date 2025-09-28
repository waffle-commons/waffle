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

    protected(set) object $config
        {
            set => $this->config = $value;
        }

    protected(set) ?System $system = null
        {
            set => $this->system = $value;
        }

    public function boot(): self
    {
        $this->config = new Configuration();

        return $this;
    }

    public function configure(): self
    {
        $this->config = $this->newAttributeInstance(
            className: $this->config,
            attribute: Configuration::class
        );

        $this->system = new System(
            security: new Security(cfg: $this->config)
        )->boot(kernel: $this);

        return $this;
    }

    public function createRequestFromGlobals(): RequestInterface
    {
        $req = new Request()->setCurrentRoute();
        if (($req->cli === false) && $this->system instanceof System && $this->system->router !== null) {
            foreach ($this->system->router->routes as $route) {
                if ($this->system->router->match(req: $req, route: $route)) {
                    $req->setCurrentRoute(route: $route);
                }
            }
        }

        return $req;
    }

    public function createCliFromRequest(): CliInterface
    {
        // TODO: Handle CLI command from request

        return new Cli(cli: false)->setCurrentRoute();
    }

    public function run(CliInterface|RequestInterface $handler): void
    {
        try {
            $handler
                ->process()
                ->render()
            ;
        } catch (Throwable $e) {
            new Response(handler: $handler)->throw(
                view: new View(data: [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace(),
                ]),
            );
        }
    }
}
