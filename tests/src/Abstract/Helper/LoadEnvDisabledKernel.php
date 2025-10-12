<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

/**
 * This is a test double for the ConcreteTestKernel.
 * Its only purpose is to disable the loadEnv() method to isolate tests
 * from the filesystem and the Dotenv component.
 */
class LoadEnvDisabledKernel extends ConcreteTestKernel
{
    #[\Override]
    public function loadEnv(bool $tests = false): void
    {
        // This method is intentionally left empty for the test.
    }
}
