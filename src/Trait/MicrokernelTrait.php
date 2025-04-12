<?php

declare(strict_types=1);

namespace Waffle\Trait;

trait MicrokernelTrait
{
    public function isCli(): bool
    {
        return match (php_sapi_name()) {
            'cli' => true,
            default => false,
        };
    }
}
