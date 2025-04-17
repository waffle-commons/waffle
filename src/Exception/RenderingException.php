<?php

namespace Waffle\Exception;

use Waffle\Trait\RenderingTrait;
use Exception;
use Throwable;

class RenderingException extends WaffleException
{
    public function __construct(string $message = "", int $code = 0)
    {
        parent::__construct($message, $code);
    }
}
