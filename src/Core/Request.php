<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractRequest;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Request extends AbstractRequest
{
    public function __construct(bool $cli = false)
    {
        $this->configure(cli: $cli);
    }
}
