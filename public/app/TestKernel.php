<?php

declare(strict_types=1);

namespace App;

use Waffle\Kernel as Base;

/**
 * This is a minimal, concrete implementation of the Waffle Kernel,
 * used exclusively for the local development server.
 * Its purpose is to boot the framework in a web context for integration testing.
 */
class TestKernel extends Base
{
}
