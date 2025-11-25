<?php

declare(strict_types=1);

namespace WaffleTests\Trait;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Waffle\Core\Constant;
use Waffle\Core\Container; // Changed target class
use Waffle\Trait\ReflectionTrait;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\Controller\TempController;
use WaffleTests\Trait\Helper\DummyAttribute;
use WaffleTests\Trait\Helper\DummyClassWithAttribute;
use WaffleTests\Trait\Helper\FinalReadOnlyClass;
use WaffleTests\Trait\Helper\NonFinalTestController;
use WaffleTests\Trait\Helper\TraitReflection;

#[CoversTrait(ReflectionTrait::class)]
final class ReflectionTraitTest extends TestCase
{
    // Use an anonymous class that uses the trait to test its methods
    private object $traitObject;
    private static null|string $staticTempDir = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->traitObject = new TraitReflection();

        // Use setUpBeforeClass for static setup if needed
        if (self::$staticTempDir === null) {
            self::$staticTempDir = sys_get_temp_dir() . '/waffle_reflection_test_' . uniqid('', true);
            if (!is_dir(self::$staticTempDir)) {
                mkdir(self::$staticTempDir, 0o777, true);
            }
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function classNameProvider(): array
    {
        /** @var string $root */
        $root = APP_ROOT;

        return [
            'Source file' => [
                $root . '/src/Core/Container.php', // Changed from Request.php
                Container::class, // Changed from Request::class
            ],
            'Test file' => [
                $root . '/tests/src/Helper/Controller/TempController.php',
                TempController::class,
            ],
        ];
    }

    /**
     * Data provider including edge cases for className test.
     * @return array<string, array{string, string}>
     */
    public static function classNameEdgeCaseProvider(): array
    {
        // Setup temp directory if not already done (e.g., if tests run in separate processes)
        if (self::$staticTempDir === null) {
            self::$staticTempDir = sys_get_temp_dir() . '/waffle_reflection_test_' . uniqid('', true);
            if (!is_dir(self::$staticTempDir)) {
                mkdir(self::$staticTempDir, 0o777, true);
            }
        }

        // File does not exist (will be handled by test logic)
        $nonExistentFile = self::$staticTempDir . '/non_existent.php';

        // File exists but has no class
        $fileWithoutClass = self::$staticTempDir . '/no_class.php';
        file_put_contents($fileWithoutClass, '<?php namespace Test; echo "hello";');

        // File exists but has no namespace
        $fileWithoutNamespace = self::$staticTempDir . '/no_namespace.php';
        file_put_contents($fileWithoutNamespace, '<?php class NoNamespaceClass {}');

        // File exists but cannot be read (simulate permission issue) - Harder to test reliably
        // We'll test the file_get_contents failure case instead.

        return array_merge(self::classNameProvider(), [
            'Non-existent file' => [$nonExistentFile, Constant::EMPTY_STRING], // Expect empty string
            'File without class' => [$fileWithoutClass, Constant::EMPTY_STRING], // Expect empty string
            'File without namespace' => [$fileWithoutNamespace, 'NoNamespaceClass'], // Expect only class name
            'File read error simulation (by providing invalid path)' => [
                'invalid/path/likely/to/fail',
                Constant::EMPTY_STRING,
            ],
        ]);
    }

    /**
     * @param string $path
     * @param string $expectedFqcn
     */
    #[DataProvider('classNameEdgeCaseProvider')]
    public function testClassNameConversion(string $path, string $expectedFqcn): void
    {
        // This test ensures that file paths are correctly converted to Fully Qualified Class Names (FQCN),
        // respecting PSR-4 mapping and handling edge cases gracefully.

        // Simulate non-existent file by ensuring it doesn't exist before calling
        if (str_contains($path, 'non_existent.php') && file_exists($path)) {
            unlink($path);
        }

        // Suppress warnings for file_get_contents on non-existent/unreadable files
        $result = @$this->traitObject->callClassName($path);

        static::assertSame($expectedFqcn, $result);
    }

    public function testNewAttributeInstance(): void
    {
        // This test validates that the method can correctly find and instantiate
        // an attribute from a given class instance.
        $classWithAttribute = new DummyClassWithAttribute();
        $attributeInstance = $this->traitObject->callNewAttributeInstance($classWithAttribute, DummyAttribute::class);

        static::assertInstanceOf(DummyAttribute::class, $attributeInstance);
        static::assertSame('test-value', $attributeInstance->value); // Accessing public property
    }

    public function testGetMethodsReturnsAllMethods(): void
    {
        // This test ensures the getMethods helper correctly retrieves all methods
        // (public, protected, etc.) from a class instance.
        $instance = new DummyClassWithAttribute();
        $methods = $this->traitObject->callGetMethods($instance); // Use helper method

        static::assertCount(2, $methods); // __construct, publicMethod, protectedMethod
        $methodNames = array_map(fn(ReflectionMethod $method): string => $method->getName(), $methods);
        static::assertContains('publicMethod', $methodNames);
        static::assertContains('protectedMethod', $methodNames);
    }

    public function testControllerValuesWithValidRoute(): void
    {
        $routeData = [
            Constant::CLASSNAME => 'App\Controller\MyController',
            Constant::METHOD => 'index',
            Constant::ARGUMENTS => ['id' => 'int'],
            Constant::PATH => '/users/{id}',
            Constant::NAME => 'user_show',
        ];

        $generator = $this->traitObject->callControllerValues($routeData);
        $result = iterator_to_array($generator);

        static::assertSame($routeData, $result, 'Generator should yield all key-value pairs from the route array.');
    }

    public function testControllerValuesWithNullRoute(): void
    {
        $generator = $this->traitObject->callControllerValues(null);
        $result = iterator_to_array($generator);

        static::assertEmpty($result, 'Generator should yield nothing if the route is null.');
    }

    public function testIsFinal(): void
    {
        $finalClass = new FinalReadOnlyClass();
        $nonFinalClass = new NonFinalTestController(); // Assuming this exists and is not final

        static::assertTrue(
            $this->traitObject->callIsFinal($finalClass),
            'isFinal should return true for a final class.',
        );
        static::assertFalse(
            $this->traitObject->callIsFinal($nonFinalClass),
            'isFinal should return false for a non-final class.',
        );
    }

    public function testIsInstance(): void
    {
        $instance = new DummyClassWithAttribute();

        static::assertTrue($this->traitObject->callIsInstance($instance, [DummyClassWithAttribute::class]));
        static::assertTrue($this->traitObject->callIsInstance($instance, [
            \stdClass::class,
            DummyClassWithAttribute::class,
        ])); // Matches one
        static::assertFalse($this->traitObject->callIsInstance($instance, [\stdClass::class, \DateTime::class])); // Matches none
        static::assertFalse($this->traitObject->callIsInstance($instance, [])); // Empty expectations
    }

    public function testGetProperties(): void
    {
        $instance = new class {
            public string $pub = 'a';
            protected int $pro = 1;
            private bool $pri = true;
        };

        $allProps = $this->traitObject->callGetProperties($instance);
        $publicProps = $this->traitObject->callGetProperties($instance, \ReflectionProperty::IS_PUBLIC);
        $protectedProps = $this->traitObject->callGetProperties($instance, \ReflectionProperty::IS_PROTECTED);
        $privateProps = $this->traitObject->callGetProperties($instance, \ReflectionProperty::IS_PRIVATE);

        static::assertCount(3, $allProps);
        static::assertCount(1, $publicProps);
        static::assertSame('pub', $publicProps[0]->getName());
        static::assertCount(1, $protectedProps);
        static::assertSame('pro', $protectedProps[0]->getName());
        static::assertCount(1, $privateProps);
        static::assertSame('pri', $privateProps[0]->getName());
    }
}
