<?php

declare(strict_types=1);

namespace Waffle\Exception;

use Waffle\Commons\Contracts\Security\Exception\SecurityExceptionInterface;

final class SecurityException extends WaffleException implements SecurityExceptionInterface
{
    public function __construct(string $message = '', int $code = 0, null|\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
