<?php

declare(strict_types=1);

namespace WaffleTests\Core\Helper;

// A service that creates a circular dependency with ServiceD.
final class ServiceE
{
    public function __construct(
        public ServiceD $serviceD,
    ) {}
}
