<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Dto;

use Waffle\Commons\Contracts\Attribute\Dto;

/**
 * Covers the float / bool / array / mixed arms of the resolver's scalar
 * type guard (assertAssignable + valueSatisfies).
 */
#[Dto]
final readonly class TypedFieldsDto
{
    /** @param array<array-key, mixed> $tags */
    public function __construct(
        public float $ratio,
        public bool $active,
        public array $tags,
        public mixed $extra,
    ) {}
}
