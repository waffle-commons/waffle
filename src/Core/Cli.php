<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractCli;
use Waffle\Enum\AppMode;
use Waffle\Interface\ContainerInterface;

class Cli extends AbstractCli
{
    /**
     * @template T
     * @param ContainerInterface $container
     * @param AppMode $cli
     * @param array{
     *       server: T|string|array<mixed>,
     *       env: T|string|array<mixed>
     *   } $globals
     */
    public function __construct(ContainerInterface $container, AppMode $cli = AppMode::CLI, array $globals = [])
    {
        $this->configure(
            container: $container,
            cli: $cli,
            globals: $globals,
        );
    }
}
