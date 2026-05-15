<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Dto;

use Waffle\Commons\Contracts\Attribute\Dto;

/**
 * DTO whose constructor parameters all have defaults or are nullable — exercises
 * the resolver's default + nullable fallback for missing keys in the body.
 */
#[Dto]
final readonly class OptionalFieldsDto
{
    public function __construct(
        public string $nickname = 'anon',
        public ?int $favoriteNumber = null,
    ) {}
}
