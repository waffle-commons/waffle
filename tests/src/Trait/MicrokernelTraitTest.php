<?php

declare(strict_types=1);

namespace WaffleTests\Trait;

use Waffle\Trait\MicrokernelTrait;
use WaffleTests\TestCase;

final class MicrokernelTraitTest extends TestCase
{
    public function testIsCliReturnsTrueWhenInCliEnvironment(): void
    {
        // --- Test Condition ---
        // We use an anonymous class that incorporates the trait to test it.
        $kernelLikeObject = new class {
            use MicrokernelTrait;
        };

        // --- Execution & Assertions ---
        // Since PHPUnit is always run from the command line, PHP_SAPI should be 'cli'.
        // We assert that the method correctly identifies this environment.
        static::assertTrue(
            $kernelLikeObject->isCli(),
            'The isCli() method should return true when running in a CLI environment.',
        );
    }
}
