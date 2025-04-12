<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractCli;
use Waffle\Interface\CliInterface;

class Cli extends AbstractCli implements CliInterface
{
    public function __construct(bool $cli = true)
    {
        $this->configure(cli: $cli);
    }
}
