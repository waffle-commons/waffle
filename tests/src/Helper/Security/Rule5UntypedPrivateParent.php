<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Security;

/**
 * Helper class used in Level 5 Security Rule test.
 * Contains an untyped private property intended for inheritance testing.
 */
class Rule5UntypedPrivateParent // Not final, intended for extension
{
    private $untypedPrivateParent; // This is not accessible to the child anyway
}
