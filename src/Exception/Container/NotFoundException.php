<?php

declare(strict_types=1);

namespace Waffle\Exception\Container;

use Waffle\Commons\Contracts\Container\Exception\NotFoundExceptionInterface;

class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
    public function __construct(string $message = 'Service not found.', int $code = 0)
    {
        parent::__construct($message, $code);
    }
}
