<?php

declare(strict_types=1);

namespace WaffleTests\Core\Helper;

// Helper class for ContainerTest: constructor with unresolvable scalar union parameter
final class ServiceWithUnionParam
{
    public string|int $id;

    public function __construct(string|int $id)
    {
        $this->id = $id;
    }
}
