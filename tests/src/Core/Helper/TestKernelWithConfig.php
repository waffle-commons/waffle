<?php

declare(strict_types=1);

namespace WaffleTests\Core\Helper;

use Waffle\Abstract\AbstractKernel;
use Waffle\Attribute\Configuration;

/**
 * A concrete Kernel implementation for testing purposes.
 * It allows injecting a specific Configuration object.
 */
class TestKernelWithConfig extends AbstractKernel
{
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }
}
