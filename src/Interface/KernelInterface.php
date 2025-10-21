<?php

declare(strict_types=1);

namespace Waffle\Interface;

interface KernelInterface
{
    public function boot(): self;

    public function configure(): self;

    public function run(CliInterface|RequestInterface $handler): void;
}
