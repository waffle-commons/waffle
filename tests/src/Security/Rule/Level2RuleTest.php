<?php

declare(strict_types=1);

namespace WaffleTests\Security\Rule;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Exception\SecurityException;
use Waffle\Security\Rule\Level2Rule;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\Security\Rule2InvalidObject;
use WaffleTests\Helper\Security\Rule2ValidObject1;
use WaffleTests\Helper\Security\Rule2ValidObject2;

#[CoversClass(Level2Rule::class)]
final class Level2RuleTest extends TestCase
{
    private Level2Rule $rule;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new Level2Rule();
    }

    /**
     * Test that check() passes for a class with only typed public properties
     * or no public properties.
     */
    public function testCheckPassesForValidClass(): void
    {
        $validObject1 = new Rule2ValidObject1();

        $validObject2 = new Rule2ValidObject2();

        // No exception should be thrown
        $this->rule->check($validObject1);
        $this->rule->check($validObject2);
        static::assertTrue(true, 'Check should pass for classes with typed or no public properties.');
    }

    /**
     * Test that check() throws SecurityException for a class with an untyped public property.
     */
    public function testCheckThrowsExceptionForUntypedPublicProperty(): void
    {
        $invalidObject = new Rule2InvalidObject();
        $className = Rule2InvalidObject::class;

        static::expectException(SecurityException::class);
        // Escape backslashes for the fully qualified class name in the regex
        $escapedClassName = str_replace('\\', '\\\\', $className);
        static::expectExceptionMessageMatches(
            "/Level 2: Public property 'untypedPublic' in {$escapedClassName} must be typed./",
        );

        $this->rule->check($invalidObject);
    }
}
