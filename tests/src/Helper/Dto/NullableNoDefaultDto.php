<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Dto;

use Waffle\Commons\Contracts\Attribute\Dto;

/**
 * Required-but-nullable field with no default — exercises the resolver's
 * `allowsNull()` fallback when the key is absent from the request body.
 */
#[Dto]
final readonly class NullableNoDefaultDto
{
    public function __construct(
        public ?string $note,
    ) {}
}
