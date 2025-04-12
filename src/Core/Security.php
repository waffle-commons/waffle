<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractSecurity;
use Waffle\Attribute\Configuration;
use Waffle\Interface\SecurityInterface;

class Security extends AbstractSecurity implements SecurityInterface
{
    public function __construct(object $cfg)
    {
        if ($cfg instanceof Configuration) {
            $this->level = $cfg->securityLevel;
        }
    }
}
