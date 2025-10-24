<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Security;

/**
 * Helper class used specifically to trigger the Level 4 Security Rule exception.
 * Contains a public method without a return type hint.
 */
final class Rule4InvalidObject
{
    public function noReturnType() // Rule 4 Violation
    {
        return 'oops';
    }

    public function typedMethod(): int
    {
        return 1;
    }
}
