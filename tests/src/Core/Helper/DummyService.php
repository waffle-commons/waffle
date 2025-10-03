<?php

declare(strict_types=1);

namespace WaffleTests\Core\Helper;

/**
 * A simple dummy service for testing dependency injection.
 */
class DummyService
{
    public function getServiceData(): array
    {
        return ['service' => 'injected', 'timestamp' => time()];
    }
}
