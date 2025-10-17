<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractRequest;
use Waffle\Enum\AppMode;
use Waffle\Interface\ContainerInterface;

class Request extends AbstractRequest
{
    public function __construct(ContainerInterface $container, AppMode $cli = AppMode::WEB)
    {
        $this->configure(
            container: $container,
            cli: $cli,
        );
    }
}
