<?php

declare(strict_types=1);

namespace WaffleTests\Core\Helper;

use Waffle\Commons\Container\Container as CommonsContainer; // Use the real component
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

        // Create the inner PSR-11 container
        $innerContainer = new CommonsContainer();

        // Instantiate Core Container with the inner container + security
        $this->container = new Container($innerContainer, $security);
    }

    #[\Override]
    public function configure(): self
    {
        if ($this->config === null) {
            throw new \LogicException('Config not set in SystemTestKernel');
        }
        if ($this->container === null) {
            $security = new Security(cfg: $this->config);
            $innerContainer = new CommonsContainer();
            $this->container = new Container($innerContainer, $security);
        }

        return $this;
    }
}
