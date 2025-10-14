<?php

declare(strict_types=1);

namespace WaffleTests\Core\Helper;

// A service that depends on ServiceA.
final class ServiceB
{
    public function __construct(
        public ServiceA $serviceA,
    ) {}
}
