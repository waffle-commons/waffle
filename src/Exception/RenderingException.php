<?php

declare(strict_types=1);

namespace Waffle\Exception;

final class RenderingException extends WaffleException
{
    public function __construct(string $message = '', int $code = 0)
    {
        parent::__construct($message, $code);
    }
}
