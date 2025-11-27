<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

use Waffle\Abstract\AbstractSecurity;use Waffle\Commons\Config\Config;use Waffle\Commons\Config\Exception\InvalidConfigurationException;

/**
 * A concrete implementation of Security for testing purposes.
 * This allows us to instantiate and test the abstract class's methods.
 */
final class ConcreteTestSecurity extends AbstractSecurity
{
    /**
     * @throws InvalidConfigurationException
     */
    public function __construct(Config $config)
    {
        $this->level = $config->getInt(
            key: 'waffle.security.level',
            default: 1,
        );
    }
}
