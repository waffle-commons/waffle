<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use ReflectionObject;
use WaffleTests\AbstractTestCase as TestCase;

final class SecurityTest extends TestCase
{
    public function testConstructorSetsSecurityLevelFromConfiguration(): void
    {
        // --- Execution ---
        // We instantiate the Security class with our configuration.
        $security = $this->createAndGetSecurity(level: 7);

        // --- Assertions ---
        // We use Reflection to access the protected 'level' property and assert
        // that it has been set correctly by the constructor.
        $reflection = new ReflectionObject($security);
        $property = $reflection->getProperty('level');
        /** @var int $level */
        $level = $property->getValue($security);

        static::assertSame(7, $level, 'The security level should be set from the configuration object.');
    }
}
