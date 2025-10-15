<?php

declare(strict_types=1);

namespace Waffle\Exception;

final class SecurityException extends WaffleException
{
    public function __construct(string $message = '', int $code = 0, null|\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
