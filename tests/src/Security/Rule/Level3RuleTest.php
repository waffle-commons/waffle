<?php

declare(strict_types=1);

namespace WaffleTests\Security\Rule;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Exception\SecurityException;
use Waffle\Security\Rule\Level3Rule;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\Security\Rule3InvalidObject;
use WaffleTests\Helper\Security\Rule3ValidObject1;
use WaffleTests\Helper\Security\Rule3ValidObject2;

#[CoversClass(Level3Rule::class)]
final class Level3RuleTest extends TestCase
{
    private Level3Rule $rule;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new Level3Rule();
    }

    /**
     * Test that check() passes for methods with valid return types (non-void).
     */
    public function testCheckPassesForValidMethods(): void
    {
        $validObject = new Rule3ValidObject1();

        // No exception should be thrown
        $this->rule->check($validObject);
        static::assertTrue(true, 'Check should pass for methods with non-void or no return type hint.');
    }

    /**
     * Test that check() throws SecurityException for a public method returning void.
     */
    public function testCheckThrowsExceptionForPublicVoidMethod(): void
    {
        $invalidObject = new Rule3InvalidObject();
        $className = Rule3InvalidObject::class;

        static::expectException(SecurityException::class);
        // Escape backslashes for the fully qualified class name in the regex
        $escapedClassName = str_replace('\\', '\\\\', $className);
        static::expectExceptionMessageMatches(
            "/Level 3: Public method 'doSomething' in {$escapedClassName} must not return 'void'./",
        );

        $this->rule->check($invalidObject);
    }

    /**
     * Test that magic methods like __construct are ignored.
     */
    public function testCheckIgnoresMagicMethods(): void
    {
        $validObject = new Rule3ValidObject2();

        // No exception should be thrown
        $this->rule->check($validObject);
        static::assertTrue(true, 'Check should ignore magic methods.');
    }
}
