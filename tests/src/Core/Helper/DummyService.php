<?php

declare(strict_types=1);

namespace WaffleTests\Core\Helper;

/**
 * A simple dummy service for testing dependency injection.
 */
final class DummyService
{
    /**
     * @return array{
     *        service: non-empty-string,
     *        timestamp: int
     *    }
     */
    public function getServiceData(): array
    {
        return ['service' => 'injected', 'timestamp' => time()];
    }
}
