<?php

declare(strict_types=1);

namespace WaffleTests\Trait\Helper;

/**
 * This helper class is designed specifically to test Security Level 6.
 * It has a typed property that is intentionally NOT initialized in the constructor.
 * This allows us to create an instance of it without calling the constructor (via reflection)
 * to test the framework's ability to detect uninitialized properties.
 */
class UninitializedPropertyClass
{
    public int $uninitializedProperty;

    public function __construct()
    {
        // The constructor is left empty on purpose. If it were called,
        // PHP would throw a fatal error because $uninitializedProperty is not set.
    }
}
