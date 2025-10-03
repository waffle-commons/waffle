<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractRequest;

final class Request extends AbstractRequest
{
    public function __construct(bool $cli = false)
    {
        $this->configure(cli: $cli);
    }
}
