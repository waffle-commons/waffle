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
class WebKernel extends Kernel
{
    private string $configDir;
    private string $testEnvironment;

    public function __construct(string $configDir, string $environment, null|ContainerInterface $container = null)
    {
        $this->configDir = $configDir;
        $this->testEnvironment = $environment;
        $this->container = $container;

        // We explicitly DO NOT call parent::__construct() here.
        // This prevents the Kernel from booting prematurely before the test environment is fully set up.
        // The boot() and configure() methods will now be called manually within each test.
    }

    #[\Override]
    public function configure(): self
    {
        // Override the configure method to inject our test-specific configuration.
        $this->config = new Config(
            configDir: $this->configDir,
            environment: $this->testEnvironment,
        );

        // Call the parent configure method to complete the setup (loading services, etc.)
        return parent::configure();
    }

    #[\Override]
    public function isCli(): bool
    {
        // Force this kernel to always identify as a web kernel,
        // unless explicitly mocked in a specific test.
        return false;
    }
}
