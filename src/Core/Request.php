<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractRequest;
use Waffle\Enum\AppMode;
use Waffle\Interface\ContainerInterface;

class Request extends AbstractRequest
{
    /**
     * @template T
     * @param ContainerInterface $container
     * @param AppMode $cli
     * @param array{
     *       server: T|string|array<mixed>,
     *       get: T|string|array<mixed>,
     *       post: T|string|array<mixed>,
     *       files: T|string|array<mixed>,
     *       cookie: T|string|array<mixed>,
     *       session: T|string|array<mixed>,
     *       request: T|string|array<mixed>,
     *       env: T|string|array<mixed>
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
