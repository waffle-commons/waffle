<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractSecurity;
use Waffle\Exception\InvalidConfigurationException;

class Security extends AbstractSecurity
{
    /**
     * @throws InvalidConfigurationException
     */
    public function __construct(Config $cfg)
    {
        $this->level = $cfg->getInt(
            key: 'waffle.security.level',
            default: 1,
        );
    }
}
