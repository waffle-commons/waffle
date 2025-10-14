<?php

declare(strict_types=1);

namespace WaffleTests\Core\Helper;

// A service that creates a circular dependency with ServiceE.
final class ServiceD
{
    public function __construct(
        public ServiceE $serviceE,
    ) {}
}
