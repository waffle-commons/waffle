<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

use Waffle\Attribute\Configuration;
use Waffle\Core\System;
use Waffle\Kernel;

/**
 * A concrete Kernel implementation for testing purposes.
 */
class ConcreteTestKernel extends Kernel
{
    // Expose protected properties for testing assertions
    public function getTestConfig(): object
    {
        return $this->config;
    }

    public function getTestSystem(): null|System
    {
        return $this->system;
    }

    // Override to use the dummy controller directory for tests
    #[\Override]
    public function boot(): self
    {
        // Create a dummy config that points to our test controllers
        $this->config = new class extends Configuration {
            public function __construct()
            {
                /** @var string $root */
                $root = APP_ROOT;
                parent::__construct(controller: $root . DIRECTORY_SEPARATOR . 'tests/src/Router/Dummy');
            }
        };

        return $this;
    }
}
