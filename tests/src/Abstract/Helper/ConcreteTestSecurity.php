<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

use Waffle\Abstract\AbstractSecurity;

/**
 * A concrete implementation of AbstractSecurity for testing purposes.
 * This allows us to instantiate and test the abstract class's methods.
 */
final class ConcreteTestSecurity extends AbstractSecurity
{
    public function __construct(object $config)
    {
        // For this test, we can use a simple stdClass as a config mock.
        // We set a default low security level, as the trait's logic is already tested elsewhere.
        $this->level = 1;
    }
}
