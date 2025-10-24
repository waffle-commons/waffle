<?php

declare(strict_types=1);

namespace WaffleTests\Core\Helper;

use Waffle\Core\Config;
use Waffle\Core\Container;
use Waffle\Core\Security;
use Waffle\Kernel; // Extend the concrete Kernel

/**
 * A concrete Kernel implementation for testing the System::boot method specifically.
 * It extends the main Kernel class to satisfy the security check expectations
 * and allows injecting a specific Configuration object.
 */
final class SystemTestKernel extends Kernel // Extends Waffle\Kernel
{
    /**
     * Overrides the constructor to allow injecting config and prevent premature boot.
     * We don't call parent::__construct() here.
     */
    public function __construct(Config $config)
    {
        // Manually set properties needed for System::boot to work
        $this->config = $config;
        $security = new Security(cfg: $config);
        $this->container = new Container(security: $security);

        // Do NOT call $this->boot() here. System::boot will call it.
    }

    // Override configure to ensure it uses the injected config/container
    #[\Override]
    public function configure(): self
    {
        // If the container/config were somehow lost, re-initialize
        if ($this->config === null) {
            throw new \LogicException('Config not set in SystemTestKernel');
        }
        if ($this->container === null) {
            $security = new Security(cfg: $this->config);
            $this->container = new Container(security: $security);
        }

        // Call the parent configure AFTER ensuring properties are set,
        // skipping the parts already done in __construct.
        // Or, more simply, just ensure the container/config are set
        // and let System::boot handle the rest of the process via Router.

        // We mainly need to ensure $this->container and $this->config are correctly populated
        // before System::boot uses them. The constructor handles this.
        // We might not even need to override configure() if the parent doesn't break things.
        // Let's rely on the constructor injection for now.
        return $this; // Return self as expected
    }

    // We might need to override boot() as well if the parent's boot logic interferes,
    // but let's try without it first. The parent boot() mainly sets up environment vars.
    /*
     * #[\Override]
     * public function boot(): self
     * {
     * // Minimal boot logic if needed, or just return self
     * // Ensure environment is set based on config maybe?
     * $this->environment = $_ENV['APP_ENV'] ?? 'prod';
     * return $this;
     * }
     */
}
