<?php

declare(strict_types=1);

namespace WaffleTests\Security\Rule;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Exception\SecurityException;
use Waffle\Security\Rule\Level9Rule;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\Security\Rule9ViolationName;
use WaffleTests\Helper\Service\NonReadOnlyService;
use WaffleTests\Trait\Helper\AbstractService;
use WaffleTests\Trait\Helper\FinalReadOnlyClass;

// A service violating the rule
// A service respecting the rule (assuming it's readonly or abstract)
// A readonly class respecting the rule

#[CoversClass(Level9Rule::class)]
final class Level9RuleTest extends TestCase
{
    private Level9Rule $rule;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new Level9Rule();
    }

    /**
     * Tests that an exception is thrown for a non-readonly service.
     */
    public function testCheckThrowsExceptionForNonReadOnlyService(): void
    {
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Level 9: Internal framework service classes must be declared readonly.');

        // Creates a service that is not readonly (simulated here, as readonly is PHP 8.1+)
        // In Waffle, we check if the name contains "Service" and the class is not readonly.
        // We use NonReadOnlyService which is not readonly.
        $violatingObject = new NonReadOnlyService();

        $this->rule->check($violatingObject);
    }

    /**
     * Tests that the exception is NOT thrown for a readonly class.
     * Although PHPUnit cannot create readonly classes dynamically,
     * we can test with a helper class that IS readonly.
     */
    public function testCheckPassesForReadOnlyClass(): void
    {
        // Use a helper class that is declared readonly.
        $compliantObject = new FinalReadOnlyClass(); // Or any other internal class that is readonly

        // No exception should be thrown.
        $this->rule->check($compliantObject);
        static::assertTrue(true); // Assertion to confirm we reached here without exception.
    }

    /**
     * Tests that the exception is NOT thrown for a class that is not an internal service.
     * Rule 9 specifically targets framework classes containing "Service".
     */
    public function testCheckPassesForNonFrameworkService(): void
    {
        // A standard object that is not readonly and does not contain "Service" in its name.
        $nonServiceObject = new Rule9ViolationName();

        // No exception should be thrown.
        $this->rule->check($nonServiceObject);
        static::assertTrue(true);
    }

    /**
     * Tests that an exception is NOT thrown for an abstract service class.
     * Abstract classes cannot be `readonly`, the rule should not apply.
     */
    public function testCheckPassesForAbstractService(): void
    {
        // We cannot instantiate an abstract class, but we can check via Reflection
        // or simply document this case. For the test, we'll simulate with a mock.
        // Ideally, the rule should ignore abstract classes.
        $abstractServiceObject = $this->createMock(AbstractService::class);

        // No exception should be thrown as the rule should ignore abstracts.
        $this->rule->check($abstractServiceObject);
        static::assertTrue(true);
    }
}
