<?php

declare(strict_types=1);

namespace Waffle\Exception\Container;

use Waffle\Commons\Contracts\Container\Exception\ContainerExceptionInterface;
use Waffle\Exception\WaffleException;

class ContainerException extends WaffleException implements ContainerExceptionInterface
{
    public function __construct(string $message = 'Container not found.', int $code = 0)
    {
        parent::__construct($message, $code);
    }
}
