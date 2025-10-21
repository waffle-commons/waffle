<?php

declare(strict_types=1);

namespace Waffle\Factory;

use Waffle\Core\Cli;
use Waffle\Enum\AppMode;
use Waffle\Exception\SecurityException;
use Waffle\Interface\CliInterface;
use Waffle\Interface\ContainerInterface;

class CliFactory
{
    /**
     * Creates a Request object from PHP's superglobals.
     * This is the single point in the application with direct access to these globals.
     *
     * @throws SecurityException
     */
    public function createFromGlobals(ContainerInterface $container): CliInterface
    {
        // TODO(@supa-chayajin): Handle CLI command from request
        $globals = [
            'server' => $_SERVER,
            'env' => $_ENV,
        ];

        return new Cli(
            container: $container,
            cli: AppMode::WEB,
            globals: $globals,
        );
    }
}
