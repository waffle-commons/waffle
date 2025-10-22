<?php

declare(strict_types=1);

namespace WaffleTests\TestsTrait;

use Waffle\Core\Cli;
use Waffle\Core\Request;
use Waffle\Enum\AppMode;

trait HttpFactoryTrait
{
    use KernelFactoryTrait;

    /**
     * @param int $level
     * @param AppMode $isCli
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
     * @return Request
     */
    protected function createRealRequest(int $level = 10, AppMode $isCli = AppMode::WEB, array $globals = []): Request
    {
        return new Request(
            container: $this->createRealContainer(level: $level),
            cli: $isCli,
            globals: $globals,
        );
    }

    /**
     * @param int $level
     * @param array{
     *       server: array<mixed>,
     *       env: array<mixed>
     *   } $globals
     * @return Cli
     */
    protected function createRealCli(int $level = 10, array $globals = []): Cli
    {
        return new Cli(
            container: $this->createRealContainer(level: $level),
            cli: AppMode::CLI,
            globals: $globals,
        );
    }
}
