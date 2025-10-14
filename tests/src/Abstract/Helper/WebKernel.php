<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

use Waffle\Core\Config;
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

    public function __construct(string $configDir, string $environment)
    {
        $this->configDir = $configDir;
        $this->testEnvironment = $environment;

        parent::__construct();
    }

    #[\Override]
    public function configure(): self
    {
        // Override the configure method to inject our test-specific configuration.
        // This ensures the Kernel uses the temporary app.yaml we create in tests.
        $this->config = new Config(
            configDir: $this->configDir,
            environment: $this->testEnvironment,
        );

        return parent::configure();
    }

    #[\Override]
    public function isCli(): bool
    {
        // Force the kernel to behave as if it's in a web environment.
        return false;
    }
}
