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
        /** @var array<string, mixed> $serverGlobals */
        $serverGlobals = $globals['server'] ?? [];
        /** @var array<string, mixed> $envGlobals */
        $envGlobals = $globals['env'] ?? [];
        $newGlobals = [
            'server' => $serverGlobals,
            'env' => $envGlobals,
        ];
        $this->configure(
            container: $container,
            cli: $cli,
            globals: $newGlobals,
        );
    }
}
