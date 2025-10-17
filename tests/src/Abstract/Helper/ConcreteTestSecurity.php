<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

use Waffle\Abstract\AbstractSecurity;
use Waffle\Core\Config;

/**
 * A concrete implementation of Security for testing purposes.
 * This allows us to instantiate and test the abstract class's methods.
 */
final class ConcreteTestSecurity extends AbstractSecurity
{
    public function __construct(Config $config)
    {
        $this->level = $config->get(
            key: 'waffle.security.level',
            default: 1,
        );
    }
}
