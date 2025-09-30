<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

use Waffle\Core\Request;

/**
 * This is a concrete implementation of the Request class, used specifically
 * for testing the behavior of the abstract classes that rely on it.
 */
final class ConcreteTestRequest extends Request
{
    // This class remains empty as it's only used to instantiate a testable object.
}
