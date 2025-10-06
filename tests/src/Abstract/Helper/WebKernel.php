<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

/**
 * This is a test double for the ConcreteTestKernel.
 * Its purpose is to force the kernel to operate in a "web" context
 * and to disable the loadEnv() method, isolating the test from the
 * filesystem and the actual execution environment (CLI).
 */
class WebKernel extends ConcreteTestKernel
{
    /**
     * @Override
     */
    #[\Override]
    public function boot(): self
    {
        // We override the boot method to inject a test-specific configuration.
        // This new configuration object points the router to our dummy controller directory.
        $this->config = new TestConfig();

        return $this;
    }

    /**
     * @Override
     */
    #[\Override]
    public function loadEnv(bool $tests = false): void
    {
        // This method is intentionally left empty for the test.
    }

    /**
     * @Override
     */
    #[\Override]
    public function isCli(): bool
    {
        // Force the kernel to behave as if it's in a web environment.
        return false;
    }
}
