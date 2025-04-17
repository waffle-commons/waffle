<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractResponse;
use Waffle\Interface\CliInterface;
use Waffle\Interface\RequestInterface;

class Response extends AbstractResponse
{
    public function __construct(CliInterface|RequestInterface $handler)
    {
        $this->build(handler: $handler);
    }
}
