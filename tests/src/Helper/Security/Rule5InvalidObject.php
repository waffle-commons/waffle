<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Security;

/**
 * Helper class used specifically to trigger the Level 5 Security Rule exception.
 * Contains an untyped private property.
 */
final class Rule5InvalidObject
{
    private $untypedPrivate; // Rule 5 Violation
    private int $typedPrivate = 1;
}
