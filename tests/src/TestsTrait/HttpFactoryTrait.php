<?php

declare(strict_types=1);

namespace WaffleTests\TestsTrait;

use Waffle\Core\Request;

trait HttpFactoryTrait
{
    use KernelFactoryTrait;

    /**
     * @template T
     * @param int $level
     * @param array{
     *       server: T|string|array<string, mixed>,
     *       get: T|string|array<string, mixed>,
     *       post: T|string|array<string, mixed>,
     *       files: T|string|array<string, mixed>,
     *       cookie: T|string|array<string, mixed>,
     *       session: T|string|array<string, mixed>,
     *       request: T|string|array<string, mixed>,
     *       env: T|string|array<string, mixed>
     *   } $globals
     * @return Request
     */
    protected function createRealRequest(int $level = 10, array $globals = []): Request
    {
        return new Request(
            container: $this->createRealContainer(level: $level),
            globals: $globals,
        );
    }
}
