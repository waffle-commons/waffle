<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

use Waffle\Attribute\Configuration;

/**
 * A dedicated test configuration class.
 * It extends the framework's base configuration to be used
 * within the test environment.
 */
#[Configuration(controller: 'tests/src/Router/Dummy', securityLevel: 1)]
final class TestConfig extends Configuration
{
}
