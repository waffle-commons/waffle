<?php

declare(strict_types=1);

namespace WaffleTests\Trait;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Waffle\Trait\ReflectionTrait;
use WaffleTests\TestCase;
use WaffleTests\Trait\Helper\DummyAttribute;
use WaffleTests\Trait\Helper\DummyClassWithAttribute;

final class ReflectionTraitTest extends TestCase
{
    use ReflectionTrait;

    #[DataProvider('classNameProvider')]
    public function testClassNameConversion(string $path, string $expectedFqcn): void
    {
        // This test ensures that file paths are correctly converted to Fully Qualified Class Names (FQCN),
        // respecting the PSR-4 mapping defined in composer.json.
        static::assertSame($expectedFqcn, $this->className($path));
    }

    public static function classNameProvider(): array
    {
        /** @var string $root */
        $root = APP_ROOT;

        return [
            'Source file' => [
                $root . '/src/Core/Request.php',
                'Waffle\Core\Request',
            ],
            'Test file' => [
                $root . '/tests/src/Router/Dummy/DummyController.php',
                'WaffleTests\Router\Dummy\DummyController',
            ],
        ];
    }

    public function testNewAttributeInstance(): void
    {
        // This test validates that the method can correctly find and instantiate
        // an attribute from a given class instance.
        $classWithAttribute = new DummyClassWithAttribute();
        $attributeInstance = $this->newAttributeInstance($classWithAttribute, DummyAttribute::class);

        static::assertInstanceOf(DummyAttribute::class, $attributeInstance);
        static::assertSame('test-value', $attributeInstance->value);
    }

    public function testGetMethodsReturnsAllMethods(): void
    {
        // This test ensures the getMethods helper correctly retrieves all methods
        // (public, protected, etc.) from a class instance.
        $instance = new DummyClassWithAttribute();
        $methods = $this->getMethods($instance);

        static::assertCount(2, $methods);
        $methodNames = array_map(fn(ReflectionMethod $method): string => $method->getName(), $methods);
        static::assertContains('publicMethod', $methodNames);
        static::assertContains('protectedMethod', $methodNames);
    }
}
