<?php

declare(strict_types=1);

namespace WaffleTests\Core\Helper;

// Helper class for ContainerTest: constructor with optional nullable parameter
final class ServiceWithOptionalParam
{
    public ?string $name;

    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }
}
