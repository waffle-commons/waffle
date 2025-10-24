<?php

declare(strict_types=1);

namespace WaffleTests\Security\Rule;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Exception\SecurityException;
use Waffle\Security\Rule\Level4Rule;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\Security\Rule4InvalidObject;
use WaffleTests\Helper\Security\Rule4TypedChildUntypedParent;
use WaffleTests\Helper\Security\Rule4ValidObject;

#[CoversClass(Level4Rule::class)]
final class Level4RuleTest extends TestCase
{
    private Level4Rule $rule;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new Level4Rule();
    }

    /**
     * Test that check() passes for classes where all public methods have return types.
     */
    public function testCheckPassesForFullyTypedMethods(): void
    {
        $validObject = new class {
            public function getString(): string
            {
                return 'hello';
            }

            public function getVoid(): void // Void is allowed by Level 4, just not *missing*
            {
            }

            protected function protectedUntyped()
            {
            } // Ignored
        };

        // No exception should be thrown
        $this->rule->check($validObject);
        static::assertTrue(true, 'Check should pass when all public methods have return types.');
    }

    /**
     * Test that check() throws SecurityException for a public method missing a return type.
     */
    public function testCheckThrowsExceptionForMissingReturnType(): void
    {
        $invalidObject = new Rule4InvalidObject();
        $className = Rule4InvalidObject::class;

        static::expectException(SecurityException::class);
        // Escape backslashes for the fully qualified class name in the regex
        $escapedClassName = str_replace('\\', '\\\\', $className);
        static::expectExceptionMessageMatches(
            "/Level 4: Public method 'noReturnType' in {$escapedClassName} must declare a return type./",
        );

        $this->rule->check($invalidObject);
    }

    /**
     * Test that inherited methods without return types don't trigger the exception
     * if the method is not declared in the checked class itself.
     */
    public function testCheckIgnoresInheritedUntypedMethods(): void
    {
        $child = new Rule4TypedChildUntypedParent();

        // No exception should be thrown for the child class
        $this->rule->check($child);
        static::assertTrue(true, 'Check should ignore untyped methods declared in parent classes.');
    }

    /**
     * Test that magic methods like __construct are ignored.
     */
    public function testCheckIgnoresMagicMethods(): void
    {
        $validObject = new Rule4ValidObject();

        // No exception should be thrown
        $this->rule->check($validObject);
        static::assertTrue(true, 'Check should ignore magic methods like __construct.');
    }
}
