<?php

declare(strict_types=1);

namespace Waffle\Trait;

trait MicrokernelTrait
{
    public function isCli(): bool
    {
        return match (PHP_SAPI) {
            'cli' => true,
            default => false,
        };
    }
}
