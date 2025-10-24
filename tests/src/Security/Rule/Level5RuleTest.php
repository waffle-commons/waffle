<?php

declare(strict_types=1);

namespace WaffleTests\Security\Rule;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Exception\SecurityException;
use Waffle\Security\Rule\Level5Rule;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\Security\Rule5InvalidObject;
use WaffleTests\Helper\Security\Rule5TypedChildUntypedParent;
use WaffleTests\Helper\Security\Rule5ValidObject1;
use WaffleTests\Helper\Security\Rule5ValidObject2;

#[CoversClass(Level5Rule::class)]
final class Level5RuleTest extends TestCase
{
    private Level5Rule $rule;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new Level5Rule();
    }

    /**
     * Test that check() passes for classes where all private properties are typed.
     */
    public function testCheckPassesForTypedPrivateProperties(): void
    {
        $validObject1 = new Rule5ValidObject1();

        $validObject2 = new Rule5ValidObject2();

        // No exception should be thrown
        $this->rule->check($validObject1);
        $this->rule->check($validObject2);
        static::assertTrue(true, 'Check should pass if all private properties are typed or none exist.');
    }

    /**
     * Test that check() throws SecurityException for an untyped private property.
     */
    public function testCheckThrowsExceptionForUntypedPrivateProperty(): void
    {
        $invalidObject = new Rule5InvalidObject();
        $className = Rule5InvalidObject::class;

        static::expectException(SecurityException::class);
        // Escape backslashes for the fully qualified class name in the regex
        $escapedClassName = str_replace('\\', '\\\\', $className);
        static::expectExceptionMessageMatches(
            "/Level 5: Private property 'untypedPrivate' in {$escapedClassName} must be typed./",
        );

        $this->rule->check($invalidObject);
    }

    /**
     * Test that inherited private properties don't trigger the exception
     * if the property is not declared in the checked class itself.
     */
    public function testCheckIgnoresInheritedUntypedPrivateProperties(): void
    {
        $child = new Rule5TypedChildUntypedParent();

        // No exception should be thrown for the child class
        $this->rule->check($child);
        static::assertTrue(true, 'Check should ignore untyped private properties declared in parent classes.');
    }
}
