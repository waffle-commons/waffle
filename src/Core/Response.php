<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractResponse;
use Waffle\Interface\CliInterface;
use Waffle\Interface\RequestInterface;
use Waffle\Interface\ResponseInterface;

class Response extends AbstractResponse implements ResponseInterface
{
    public function __construct(CliInterface|RequestInterface $handler)
    {
        $this->build(handler: $handler);
    }
}
