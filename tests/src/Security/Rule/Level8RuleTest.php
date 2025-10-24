<?php

declare(strict_types=1);

namespace WaffleTests\Security\Rule;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Exception\SecurityException;
use Waffle\Security\Rule\Level8Rule;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\Security\Rule8FinalController;
use WaffleTests\Helper\Security\Rule8ViolationName;
use WaffleTests\Trait\Helper\NonFinalTestController; // Use existing helper

#[CoversClass(Level8Rule::class)]
final class Level8RuleTest extends TestCase
{
    private Level8Rule $rule;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new Level8Rule();
    }

    /**
     * Test that check() passes for final controllers or non-controller classes.
     */
    public function testCheckPassesForValidClasses(): void
    {
        $finalController = new Rule8FinalController();
        $nonController = new Rule8ViolationName();

        // No exception should be thrown
        $this->rule->check(new $finalController());
        $this->rule->check($nonController);
        static::assertTrue(true, 'Check should pass for final controllers and non-controllers.');
    }

    /**
     * Test that check() throws SecurityException for a non-final controller class.
     */
    public function testCheckThrowsExceptionForNonFinalController(): void
    {
        $invalidObject = new NonFinalTestController(); // Use the helper class
        $className = NonFinalTestController::class;

        static::expectException(SecurityException::class);
        static::expectExceptionMessage(
            'Level 8: Controller classes must be declared final to prevent unintended extension.',
        );

        $this->rule->check($invalidObject);
    }
}
