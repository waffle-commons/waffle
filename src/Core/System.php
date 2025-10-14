<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractKernel;
use Waffle\Abstract\AbstractSystem;
use Waffle\Attribute\Configuration;
use Waffle\Exception\SecurityException;
use Waffle\Interface\KernelInterface;
use Waffle\Kernel;
use Waffle\Router\Router;

class System extends AbstractSystem
{
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[\Override]
    public function boot(KernelInterface $kernel): self
    {
        try {
            /** @var Kernel $kernel */
            $this->security->analyze(
                object: $kernel,
                expectations: [
                    Kernel::class,
                    AbstractKernel::class,
                    KernelInterface::class,
                ],
            );
            $this->security->analyze(
                object: $kernel->config,
                expectations: [
                    Config::class,
                ],
            );
            $controllers = $kernel->config->get(key: 'waffle.paths.controllers');
            $this->registerRouter(
                router: new Router(
                    directory: APP_ROOT . DIRECTORY_SEPARATOR . $controllers,
                    system: $this,
                )
                    ->boot()
                    ->registerRoutes(container: $kernel->container),
            );
        } catch (SecurityException $e) {
            $e->throw(view: new View(data: $e->serialize()));
        }

        return $this;
    }
}
