<?php

declare(strict_types=1);

namespace WaffleTests\Core\Helper;

use Waffle\Abstract\AbstractKernel;use Waffle\Commons\Config\Config;use Waffle\Core\Container;use Waffle\Core\Security;

/**
 * A concrete Kernel implementation for testing purposes.
 * It allows injecting a specific Configuration object.
 */
final class TestKernelWithConfig extends AbstractKernel
{
    public function __construct(Config $config)
    {
        $this->config = $config;
        $security = new Security(cfg: $config);
        $this->container = new Container(security: $security);
    }
}
