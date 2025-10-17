<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractRequest;
use Waffle\Enum\AppMode;
use Waffle\Interface\ContainerInterface;

class Request extends AbstractRequest
{
    /**
     * @param ContainerInterface $container
     * @param AppMode $cli
     * @param array{
     *       server: array<mixed>,
     *       get: array<mixed>,
     *       post: array<mixed>,
     *       files: array<mixed>,
     *       cookie: array<mixed>,
     *       session: array<mixed>,
     *       request: array<mixed>,
     *       env: array<mixed>
     *   } $globals
     */
    public function __construct(ContainerInterface $container, AppMode $cli, array $globals = [])
    {
        $this->configure(
            container: $container,
            cli: $cli,
            globals: $globals,
        );
    }
}
