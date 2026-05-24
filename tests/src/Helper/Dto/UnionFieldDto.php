<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Dto;

use Waffle\Commons\Contracts\Attribute\Dto;

/**
 * Union-typed field — exercises the resolver's union-type pre-validation
 * (a value satisfying *any* member of the union is accepted).
 */
#[Dto]
final readonly class UnionFieldDto
{
    public function __construct(
        public int|string $value,
    ) {}
}
