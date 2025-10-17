<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractCli;
use Waffle\Enum\AppMode;
use Waffle\Interface\ContainerInterface;

class Cli extends AbstractCli
{
    public function __construct(ContainerInterface $container, AppMode $cli = AppMode::CLI)
    {
        $this->configure(
            container: $container,
            cli: $cli,
        );
    }
}
