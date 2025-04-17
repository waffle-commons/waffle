<?php

declare(strict_types=1);

namespace Waffle\Interface;

use Waffle\Router\Router;

interface SystemInterface
{
    public function boot(KernelInterface $kernel): self;

    public function registerRouter(Router $router): void;
}
