<?php

declare(strict_types=1);

namespace WaffleTests\Trait;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use stdClass;
use Waffle\Exception\SecurityException;
use Waffle\Trait\SecurityTrait;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\NonReadOnlyService;
use WaffleTests\Trait\Helper\FinalReadOnlyClass;
use WaffleTests\Trait\Helper\NonFinalTestController;
use WaffleTests\Trait\Helper\UninitializedPropertyClass;

#[CoversTrait(SecurityTrait::class)]
final class SecurityTraitTest extends TestCase
{
    use SecurityTrait;

    /**
     * @param object $object
     * @param string[] $expectations
     * @return void
     */
    #[DataProvider('validExpectationsProvider')]
    public function testIsValidReturnsTrueForMatchingExpectations(object $object, array $expectations): void
    {
        static::assertTrue($this->isValid($object, $expectations));
    }

    public static function validExpectationsProvider(): array
    {
        return [
            'Object matches its own class' => [new stdClass(), [stdClass::class]],
        ];
    }

    /**
     * @param object $object
     * @param string[] $expectations
     * @return void
     */
    #[DataProvider('mismatchedExpectationsProvider')]
    public function testIsValidReturnsFalseForMismatchedExpectations(object $object, array $expectations): void
    {
        static::assertFalse($this->isValid($object, $expectations));
    }

    public static function mismatchedExpectationsProvider(): array
    {
        return [
            'Object does not match class' => [new stdClass(), [self::class]],
            'Object does not match any expectation' => [new stdClass(), [TestCase::class, 'ArrayObject']],
        ];
    }

    #[DataProvider('securityViolationProvider')]
    public function testIsSecureThrowsExceptionOnViolation(
        object $violatingObject,
        int $securityLevel,
        string $expectedExceptionMessage,
    ): void {
        static::expectException(SecurityException::class);
        static::expectExceptionMessageMatches($expectedExceptionMessage);

        $this->isSecure(
            object: $violatingObject,
            level: $securityLevel,
        );
    }

    public function testIsSecurePassesWithValidObject(): void
    {
        $validObject = new FinalReadOnlyClass();

        $this->isSecure(
            object: $validObject,
            level: 1,
        );
        $this->isSecure(
            object: $validObject,
            level: 5,
        );
        $this->isSecure(object: $validObject);

        static::assertTrue(true);
    }

    /**
     * This test is special because it validates Security Level 6, which checks for
     * uninitialized properties. To achieve this, we must bypass the normal object
     * instantiation process, as PHP would otherwise throw a fatal error.
     * We use Reflection to create an instance *without* calling its constructor,
     * leaving its properties in the uninitialized state we need to detect.
     *
     * @throws \ReflectionException
     */
    public function testIsSecureThrowsExceptionForUninitializedPropertyLevel6(): void
    {
        static::expectException(SecurityException::class);
        $msg6 =
            'Level 6: Property \'uninitializedProperty\' '
            . 'in WaffleTests\Trait\Helper\UninitializedPropertyClass is not initialized.';
        static::expectExceptionMessage($msg6);

        // 1. Use Reflection to get the class blueprint.
        $reflectionClass = new ReflectionClass(UninitializedPropertyClass::class);

        // 2. Create an instance WITHOUT calling the constructor.
        // This is the key to this test, as it leaves the property uninitialized.
        $objectWithoutConstructor = $reflectionClass->newInstanceWithoutConstructor();

        // 3. Action: Run the security check. It should now detect the uninitialized property.
        $this->isSecure(
            object: $objectWithoutConstructor,
            level: 6,
        );
    }

    public function testIsSecureThrowsExceptionForNonReadOnlyServiceLevel9(): void
    {
        static::expectException(SecurityException::class);
        static::expectExceptionMessage('Level 9: Internal framework service classes must be declared readonly.');

        // We create a dummy class that simulates a framework service that is not readonly.
        $nonReadOnlyService = new NonReadOnlyService();

        $this->isSecure(
            object: $nonReadOnlyService,
            level: 9,
        );
    }

    public static function securityViolationProvider(): \Generator
    {
        // Level 2 Violation: An untyped public property.
        $msg2 = "/Level 2: Public property 'untypedProperty' in class@anonymous.* must be typed./";
        yield 'Level 2 Violation: Untyped public property' => [
            'violatingObject' => new class {
                public $untypedProperty;
            },
            'securityLevel' => 2,
            'expectedExceptionMessage' => $msg2,
        ];

        // Level 3 Violation: A public method returning void.
        $msg3 = "/Level 3: Public method 'getSomething' in class@anonymous.* must not return 'void'./";
        yield 'Level 3 Violation: Public method returns void' => [
            'violatingObject' => new class {
                public function getSomething(): void
                {
                }
            },
            'securityLevel' => 3,
            'expectedExceptionMessage' => $msg3,
        ];

        // Level 4 Violation: A public method with no declared return type.
        $msg4 = "/Level 4: Public method 'getSomething' in class@anonymous.* must declare a return type./";
        yield 'Level 4 Violation: A public method with no declared return type' => [
            'violatingObject' => new class {
                public function getSomething()
                {
                }
            },
            'securityLevel' => 4,
            'expectedExceptionMessage' => $msg4,
        ];

        // Level 5 Violation: An untyped private property.
        $msg5 = "/Level 5: Private property 'untypedPrivate' in class@anonymous.* must be typed./";
        yield 'Level 5 Violation: Untyped private property' => [
            'violatingObject' => new class {
                private $untypedPrivate;
            },
            'securityLevel' => 5,
            'expectedExceptionMessage' => $msg5,
        ];

        // Level 7 Violation: A public method argument that is not strictly typed.
        $msg7 = "/Level 7: Public method 'doSomething' parameter '_untypedArgument' must be strictly typed./";
        yield 'Level 7 Violation: Untyped public method argument' => [
            'violatingObject' => new class {
                public function doSomething(mixed $_untypedArgument): int
                {
                    return 1;
                }
            },
            'securityLevel' => 7,
            'expectedExceptionMessage' => $msg7,
        ];

        // Level 8 Violation: A Controller class that is not declared as final.
        $msg8 = '/Level 8: Controller classes must be declared final to prevent unintended extension./';
        yield 'Level 8 Violation: Controller not final' => [
            'violatingObject' => new NonFinalTestController(),
            'securityLevel' => 8,
            'expectedExceptionMessage' => $msg8,
        ];

        // Level 9 Violation: Impossible to test on the framework

        // Level 10 Violation: A class that is not declared as final.
        yield 'Level 10 Violation: Class not final' => [
            'violatingObject' => new class {},
            'securityLevel' => 10,
            'expectedExceptionMessage' => '/Level 10: All classes must be declared final./',
        ];
    }
}
