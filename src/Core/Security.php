<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractSecurity;
use Waffle\Attribute\Configuration;

class Security extends AbstractSecurity
{
    public function __construct(Config $cfg)
    {
        $this->level = $cfg->get(key: 'waffle.security.level');
    }
}
