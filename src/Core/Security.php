<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractSecurity;
use Waffle\Attribute\Configuration;

class Security extends AbstractSecurity
{
    public function __construct(object $cfg)
    {
        if ($cfg instanceof Configuration) {
            $this->level = $cfg->securityLevel;
        }
    }
}
