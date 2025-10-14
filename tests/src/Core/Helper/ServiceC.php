<?php

declare(strict_types=1);

namespace WaffleTests\Core\Helper;

// A service with a nested dependency (C -> B -> A).
final class ServiceC
{
    public function __construct(
        public ServiceB $serviceB,
    ) {}
}
