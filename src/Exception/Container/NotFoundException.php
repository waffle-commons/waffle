<?php

declare(strict_types=1);

namespace Waffle\Exception\Container;

class NotFoundException extends ContainerException
{
    public function __construct(string $message = 'Service not found.', int $code = 0)
    {
        parent::__construct($message, $code);
    }
}
