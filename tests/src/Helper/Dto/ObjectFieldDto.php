<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Dto;

use stdClass;
use Waffle\Commons\Contracts\Attribute\Dto;

/**
 * Class/object-typed field — a JSON scalar can never satisfy it, so the
 * resolver rejects it up-front (the `default => false` arm of valueSatisfies).
 */
#[Dto]
final readonly class ObjectFieldDto
{
    public function __construct(
        public stdClass $obj,
    ) {}
}
