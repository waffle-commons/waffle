<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Dto;

use ArrayAccess;
use Countable;
use Waffle\Commons\Contracts\Attribute\Dto;

/**
 * Intersection-typed field — neither a single named type nor a union, so the
 * resolver defers type-checking to the constructor (the early-return path of
 * assertAssignable).
 */
#[Dto]
final readonly class IntersectionFieldDto
{
    public function __construct(
        public Countable&ArrayAccess $bag,
    ) {}
}
