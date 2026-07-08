<?php

declare(strict_types=1);

namespace Waffle;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Waffle\Abstract\AbstractKernel;
use Waffle\Commons\Contracts\Config\ConfigInterface;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Contracts\Pipeline\MiddlewareStackInterface;
use Waffle\Commons\Contracts\Security\SecurityInterface;

class Kernel extends AbstractKernel
{
    public function __construct(
        ConfigInterface $config,
        ContainerInterface $container,
        SecurityInterface $security,
        MiddlewareStackInterface $middlewareStack,
        LoggerInterface $logger = new NullLogger(),
    ) {
        parent::__construct($config, $container, $security, $middlewareStack, $logger);
        $this->boot();
    }
}
