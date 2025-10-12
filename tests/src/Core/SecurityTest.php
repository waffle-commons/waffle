<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Waffle\Attribute\Configuration;
use Waffle\Core\Security;

final class SecurityTest extends TestCase
{
    public function testConstructorSetsSecurityLevelFromConfiguration(): void
    {
        // --- Test Condition ---
        // We create a configuration object with a specific security level.
        $config = new Configuration(securityLevel: 7);

        // --- Execution ---
        // We instantiate the Security class with our configuration.
        $security = new Security(cfg: $config);

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
