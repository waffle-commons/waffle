<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

use Waffle\Core\Config;
use Waffle\Interface\ContainerInterface;
use Waffle\Kernel;

/**
 * This is a test double for the concrete Kernel.
 * Its purpose is to force the kernel to operate in a "web" context
 * and to use a specific, temporary configuration directory for test isolation.
 */
class CliKernel extends WebKernel
{
    #[\Override]
    public function isCli(): bool
    {
        // Force this kernel to always identify as a cli kernel,
        // unless explicitly mocked in a specific test.
        return true;
    }
}
