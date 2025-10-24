<?php

declare(strict_types=1);

namespace WaffleTests\Core\Helper;

// Helper class for ContainerTest: constructor with default scalar parameter
final class ServiceWithDefaultParam
{
    public int $count;

    public function __construct(int $count = 5)
    {
        $this->count = $count;
    }
}
