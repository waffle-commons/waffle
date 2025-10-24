<?php

declare(strict_types=1);

namespace WaffleTests\Security\Rule;

use PHPUnit\Framework\Attributes\CoversClass;
use stdClass; // Use a standard class for testing
use Waffle\Security\Rule\Level1Rule;
use WaffleTests\AbstractTestCase as TestCase;

#[CoversClass(Level1Rule::class)]
final class Level1RuleTest extends TestCase
{
    private Level1Rule $rule;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new Level1Rule();
    }

    /**
     * Test that check() passes for a valid object instance.
     * Level 1 only checks if the object is an instance of its own class.
     */
    public function testCheckPassesForValidObject(): void
    {
        $validObject = new stdClass();

        // No exception should be thrown
        $this->rule->check($validObject);
        static::assertTrue(true, 'Check should pass for a standard object.');
    }
}
