<?php

declare(strict_types=1);

namespace WaffleTests\Security\Rule;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Exception\SecurityException;
use Waffle\Security\Rule\Level7Rule;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\Security\Rule7ChildMethod;
use WaffleTests\Helper\Security\Rule7InvalidObject;
use WaffleTests\Helper\Security\Rule7ValidObject;

#[CoversClass(Level7Rule::class)]
final class Level7RuleTest extends TestCase
{
    private Level7Rule $rule;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new Level7Rule();
    }

    /**
     * Test that check() passes when all public method arguments are strictly typed.
     */
    public function testCheckPassesForStrictlyTypedArguments(): void
    {
        $validObject = new Rule7ValidObject();

        // No exception should be thrown
        $this->rule->check($validObject);
        static::assertTrue(true, 'Check should pass for strictly typed arguments.');
    }

    /**
     * Test that check() throws SecurityException for a non-strictly typed ('mixed' without default) argument.
     */
    public function testCheckThrowsExceptionForMixedArgumentWithoutDefault(): void
    {
        $invalidObject = new Rule7InvalidObject();
        $className = Rule7InvalidObject::class;

        static::expectException(SecurityException::class);
        static::expectExceptionMessageMatches(
            "/Level 7: Public method 'process' parameter '_untypedArgument' must be strictly typed./",
        );

        $this->rule->check($invalidObject);
    }

    /**
     * Test that methods declared in parent classes are ignored.
     */
    public function testCheckIgnoresInheritedMethods(): void
    {
        $child = new Rule7ChildMethod();

        // No exception should be thrown for the child instance
        $this->rule->check($child);
        static::assertTrue(true, 'Check should ignore methods declared in parent classes.');
    }
}
