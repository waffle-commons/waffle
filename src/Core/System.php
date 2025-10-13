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
            /** @var Configuration $config */
            $config = $kernel->config;
            $this->security->analyze(
                object: $config,
                expectations: [
                    Configuration::class,
                ],
            );
            $this->registerRouter(
                router: new Router(
                    directory: $config->controllerDir,
                    system: $this,
                    container: $kernel->container,
                )
                    ->boot()
                    ->registerRoutes(),
            );
        } catch (SecurityException $e) {
            $e->throw(view: new View(data: $e->serialize()));
        }

        return $this;
    }
}
