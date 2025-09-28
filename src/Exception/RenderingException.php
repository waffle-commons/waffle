<?php

namespace Waffle\Exception;

class RenderingException extends WaffleException
{
    public function __construct(string $message = "", int $code = 0)
    {
        parent::__construct($message, $code);
    }
}
