<?php

declare(strict_types=1);

namespace WaffleTests\Security\Rule;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Exception\SecurityException;
use Waffle\Security\Rule\Level10Rule;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\Security\Rule10ViolatingObject;
use WaffleTests\Trait\Helper\FinalReadOnlyClass; // A final class

#[CoversClass(Level10Rule::class)]
final class Level10RuleTest extends TestCase
{
    private Level10Rule $rule;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new Level10Rule();
    }

    /**
     * Tests that an exception is thrown for a non-final class.
     */
    public function testCheckThrowsExceptionForNonFinalClass(): void
    {
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Level 10: All classes must be declared final.');

        // Creates an instance of an anonymous class (which is not final by default).
        $violatingObject = new Rule10ViolatingObject();

        $this->rule->check($violatingObject);
    }

    /**
     * Tests that the exception is NOT thrown for a final class.
     */
    public function testCheckPassesForFinalClass(): void
    {
        // Uses a helper class that is declared final.
        $compliantObject = new FinalReadOnlyClass(); // This class is final and readonly

        // No exception should be thrown.
        $this->rule->check($compliantObject);
        static::assertTrue(true); // Assertion to confirm we reached here without exception.
    }
}
