<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Security;

/**
 * Helper class used specifically to trigger the Level 3 Security Rule exception.
 * Contains a public method returning void.
 */
final class Rule3InvalidObject
{
    public function doSomething(): void // Rule 3 Violation
    {
        // Action
    }

    public function alsoValid(): int
    {
        return 1;
    }
}
