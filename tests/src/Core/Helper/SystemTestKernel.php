<?php

declare(strict_types=1);

namespace WaffleTests\Core\Helper;

use Waffle\Core\Config;
use Waffle\Core\Container;
use Waffle\Core\Security;
use Waffle\Kernel;

/**
 * A concrete Kernel implementation for testing the System::boot method specifically.
 */
final class SystemTestKernel extends Kernel
{
    public function __construct(Config $config)
    {
        // Manually set properties needed for System::boot to work
        $this->config = $config;
        $security = new Security(cfg: $config);

        // Try to use real container if available, otherwise use a minimal mock-like structure via reflection?
        // Ideally, we should use the real container component if we are testing integration.
        // If unavailable, we can't easily mock the inner container here without dependency injection.
        // Assuming waffle-commons/container IS available in require-dev environment.

        if (class_exists('Waffle\Commons\Container\Container')) {
            $innerContainer = new \Waffle\Commons\Container\Container();
            $this->container = new Container($innerContainer, $security);
        } else {
            // Fallback for strict isolation (might fail if tests rely on real container behavior)
            throw new \RuntimeException('Waffle\Commons\Container\Container not found. Install dev dependencies.');
        }
    }

    #[\Override]
    public function configure(): self
    {
        if ($this->config === null) {
            throw new \LogicException('Config not set in SystemTestKernel');
        }
        if ($this->container === null) {
            $security = new Security(cfg: $this->config);
            if (class_exists('Waffle\Commons\Container\Container')) {
                $innerContainer = new \Waffle\Commons\Container\Container();
                $this->container = new Container($innerContainer, $security);
            }
        }

        return $this;
    }
}
