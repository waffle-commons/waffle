<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Security;

/**
 * Helper class used specifically to trigger the Level 2 Security Rule exception.
 * Contains an untyped public property.
 */
final class Rule2InvalidObject
{
    public $untypedPublic; // Rule 2 Violation
    public string $typedPublic = 'ok';
}
