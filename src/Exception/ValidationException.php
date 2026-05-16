<?php

declare(strict_types=1);

namespace Waffle\Exception;

use Throwable;
use Waffle\Commons\Contracts\Exception\Validation\ValidationExceptionInterface;

/**
 * Thrown when DTO hydration fails (missing required key, wrong shape) or when a
 * PHP 8.5 Property Hook rejects an input value inside a `#[Dto]`-marked class.
 *
 * The default code (422) is the HTTP status the JsonErrorRenderer will emit
 * via the RFC 7807 mapping; the renderer also surfaces `field` in the payload
 * when present.
 */
class ValidationException extends WaffleException implements ValidationExceptionInterface
{
    public function __construct(
        string $message,
        private(set) ?string $field = null,
        int $code = 422,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    #[\Override]
    public function getField(): ?string
    {
        return $this->field;
    }
}
