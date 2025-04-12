<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractRequest;
use Waffle\Interface\RequestInterface;

class Request extends AbstractRequest implements RequestInterface
{
    public function __construct(bool $cli = false)
    {
        $this->configure(cli: $cli);
    }
}
