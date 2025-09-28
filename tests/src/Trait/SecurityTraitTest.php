<?php

declare(strict_types=1);

namespace WaffleTests\Trait;

use Generator;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Waffle\Exception\SecurityException;
use Waffle\Trait\SecurityTrait;

#[CoversTrait(traitName: SecurityTrait::class)]
final class SecurityTraitTest extends TestCase
{
    use SecurityTrait;

    /**
     * This test ensures that the isSecure method correctly throws a SecurityException
     * when an object violates the rules of a given security level.
     *
     * @param object $violatingObject The object instance that is expected to fail the security check.
     * @param int $securityLevel The security level to test against.
     * @param string $expectedExceptionMessagePattern The regex pattern for the expected exception message.
     */
    #[DataProvider('securityViolationProvider')]
    public function testIsSecureThrowsExceptionOnViolation(object $violatingObject, int $securityLevel, string $expectedExceptionMessagePattern): void
    {
        // Expect a SecurityException to be thrown.
        $this->expectException(SecurityException::class);
        // Use a regex match to ignore the file path and line number in the anonymous class name.
        $this->expectExceptionMessageMatches($expectedExceptionMessagePattern);

        // Execute the method that should trigger the exception.
        $this->isSecure(object: $violatingObject, level: $securityLevel);
    }

    /**
     * This test ensures that a perfectly compliant object passes all security levels
     * without throwing any exceptions.
     */
    public function testIsSecurePassesWithValidObject(): void
    {
        // A final readonly class is compliant with all security levels up to 10.
        $validObject = new FinalReadOnlyClass();

        // Assert that no exception is thrown for various security levels.
        $this->assertTrue($this->isSecure(object: $validObject, level: 1));
        $this->assertTrue($this->isSecure(object: $validObject, level: 5));
        $this->assertTrue($this->isSecure(object: $validObject, level: 10));
    }

    /**
     * Provides a set of test cases where security rules are violated.
     *
     * Each yielded array contains:
     * - An object specifically crafted to violate a rule.
     * - The security level at which the violation should be detected.
     * - The regex pattern for the expected exception message.
     */
    public static function securityViolationProvider(): Generator
    {
        // Level 2 Violation: A public property that is not typed.
        yield 'Level 2 Violation: Untyped public property' => [
            'violatingObject' => new class {
                public $untypedProperty;
            },
            'securityLevel' => 2,
            'expectedExceptionMessagePattern' => "/^Level 2: Public property 'untypedProperty' in class@anonymous.* must be typed\.$/",
        ];

        // Level 3 Violation: A public method explicitly returning 'void'.
        yield 'Level 3 Violation: Public method returns void' => [
            'violatingObject' => new class {
                public function getSomething(): void {}
            },
            'securityLevel' => 3,
            'expectedExceptionMessagePattern' => "/^Level 3: Public method 'getSomething' in class@anonymous.* must not be of 'void' type\.$/",
        ];

        // Level 4 Violation: A public method with no declared return type.
        yield 'Level 4 Violation: A public method with no declared return type' => [
            'violatingObject' => new class {
                public function getSomething() {}
            },
            'securityLevel' => 4,
            'expectedExceptionMessagePattern' => "/^Level 4: Public method 'getSomething' in class@anonymous.* must declare a return type\.$/",
        ];

        // Level 5 Violation: A private property that is not typed.
        yield 'Level 5 Violation: Untyped private property' => [
            'violatingObject' => new class {
                private $untypedPrivate;
            },
            'securityLevel' => 5,
            'expectedExceptionMessagePattern' => "/^Level 5: Private property 'untypedPrivate' in class@anonymous.* must be typed\.$/",
        ];

        // Level 7 Violation: A public method with a 'mixed' type argument.
        yield 'Level 7 Violation: Untyped public method argument' => [
            'violatingObject' => new class {
                public function doSomething(mixed $untypedArgument): int
                {
                    return 1;
                }
            },
            'securityLevel' => 7,
            'expectedExceptionMessagePattern' => "/^Level 7: Public method 'doSomething' parameter 'untypedArgument' must be strictly typed\.$/",
        ];

        // Level 8 Violation: A class with 'Controller' in its name that is not final.
        yield 'Level 8 Violation: Controller not final' => [
            'violatingObject' => new NonFinalTestController(),
            'securityLevel' => 8,
            'expectedExceptionMessagePattern' => '/^Level 8: Controller classes must be declared final to prevent unintended extension\.$/',
        ];

        // Level 9 Violation: A class with 'Service' in its name that is not readonly.
        yield 'Level 9 Violation: Service not readonly' => [
            'violatingObject' => new NonReadonlyTestService(),
            'securityLevel' => 9,
            'expectedExceptionMessagePattern' => '/^Level 9: Service classes must be declared readonly\.$/',
        ];

        // Level 10 Violation: A class that is not final.
        yield 'Level 10 Violation: Class not final' => [
            'violatingObject' => new class {},
            'securityLevel' => 10,
            'expectedExceptionMessagePattern' => '/^Level 10: All classes must be declared final\.$/',
        ];
    }
}

// --- Helper classes for specific violation tests ---

/**
 * A helper class that is fully compliant with all security rules up to level 10.
 */
final readonly class FinalReadOnlyClass {}

/**
 * A helper class that violates security rule #8 by containing "Controller" in its name
 * but not being declared as final.
 */
class NonFinalTestController {}

/**
 * A helper class that violates security rule #9 by containing "Service" in its name
 * but not being declared as readonly.
 */
class NonReadonlyTestService {}

