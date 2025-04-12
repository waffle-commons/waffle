<?php

declare(strict_types=1);

namespace Waffle\Interface;

interface SystemInterface
{
    public function boot(KernelInterface $kernel): self;
}
