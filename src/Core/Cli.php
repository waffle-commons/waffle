<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractCli;
use Waffle\Interface\ContainerInterface;

class Cli extends AbstractCli
{
    public function __construct(ContainerInterface $container, bool $cli = true)
    {
        $this->configure(
            container: $container,
            cli: $cli,
        );
    }
}
