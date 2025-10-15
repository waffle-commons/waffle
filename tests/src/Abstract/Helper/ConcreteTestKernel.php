<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

use Waffle\Core\System;
use Waffle\Kernel;

/**
 * A concrete Kernel implementation for testing purposes.
 */
class ConcreteTestKernel extends Kernel
{
    #[\Override]
    public function boot(): self
    {
        return $this;
    }
}
