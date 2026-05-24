<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Dto;

use InvalidArgumentException;
use Waffle\Commons\Contracts\Attribute\Dto;

/**
 * Fixture for Beta-1 Phase 3 (Task 3.2): a DTO whose Property Hook rejects bad
 * input using PHP's standard `\InvalidArgumentException` rather than a typed
 * `Waffle\Exception\ValidationException`. The resolver MUST translate this into
 * a unified `ValidationException` so the JsonErrorRenderer can still emit a
 * 422 response.
 */
#[Dto]
final class PlainRejectionDto
{
    public string $code {
        set(string $value) {
            if ($value === '') {
                throw new InvalidArgumentException('code must not be empty.');
            }
            $this->code = $value;
        }
    }

    public function __construct(string $code)
    {
        $this->code = $code;
    }
}
