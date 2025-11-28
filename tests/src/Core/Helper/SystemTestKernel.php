<?php

declare(strict_types=1);

namespace WaffleTests\Core\Helper;

use Waffle\Commons\Contracts\Config\ConfigInterface;
use Waffle\Commons\Contracts\Security\SecurityInterface;
use Waffle\Kernel;
use WaffleTests\Helper\MockContainer;

/**
 * A concrete Kernel implementation for testing the System::boot method specifically.
 */
final class SystemTestKernel extends Kernel
{
    public function __construct(ConfigInterface $config)
    {
        // Manually set properties needed for System::boot to work
        $this->config = $config;
        // Mock Security for testing
        $security = new class implements SecurityInterface {
            public function analyze(object $object, array $expectations = []): void
            {
            }
        };

        // Use MockContainer for testing
        $innerContainer = new MockContainer();
        $this->container = $innerContainer;
    }

    #[\Override]
    public function configure(): self
    {
        if ($this->config === null) {
            throw new \LogicException('Config not set in SystemTestKernel');
        }
        if ($this->container === null) {
            $security = new class implements SecurityInterface {
                public function analyze(object $object, array $expectations = []): void
                {
                }
            };
            $innerContainer = new MockContainer();
            $this->container = $innerContainer;
        }

        return $this;
    }
}
