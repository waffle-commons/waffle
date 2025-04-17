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

    public function boot(KernelInterface $kernel): self
    {
        try {
            /** @var Kernel $kernel */
            $this->security->analyze(object: $kernel, expectations: [
                Kernel::class,
                AbstractKernel::class,
                KernelInterface::class,
            ]);
            if ($kernel->config instanceof Configuration) {
                $this->registerRouter(
                    router: new Router(directory: $kernel->config->controllerDir)
                        ->boot()
                        ->registerRoutes()
                );
            }
        } catch (SecurityException $e) {
            $e->throw(view: new View(data: $e->serialize()));
        } finally {
            return $this;
        }
    }
}
