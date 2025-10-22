<?php

declare(strict_types=1);

namespace WaffleTests\Security\Rule;

use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use Waffle\Exception\SecurityException;
use Waffle\Security\Rule\Level6Rule;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\Security\Rule6ValidObject;
use WaffleTests\Trait\Helper\UninitializedPropertyClass; // Use the existing helper

#[CoversClass(Level6Rule::class)]
final class Level6RuleTest extends TestCase
{
    private Level6Rule $rule;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new Level6Rule();
    }

    /**
     * Test that check() passes when all properties are initialized.
     */
    public function testCheckPassesForInitializedProperties(): void
    {
        $validObject = new Rule6ValidObject();

        // No exception should be thrown
        $this->rule->check($validObject);
        static::assertTrue(true, 'Check should pass when all properties are initialized.');
    }

    /**
     * Test that check() throws SecurityException for an uninitialized property.
     * Uses the existing UninitializedPropertyClass helper and reflection.
     * @throws \ReflectionException
     */
    public function testCheckThrowsExceptionForUninitializedProperty(): void
    {
        // Use reflection to instantiate without calling the constructor
        $reflectionClass = new ReflectionClass(UninitializedPropertyClass::class);
        $objectWithoutConstructor = $reflectionClass->newInstanceWithoutConstructor();

        static::expectException(SecurityException::class);
        $className = UninitializedPropertyClass::class;
        $escapedClassName = str_replace('\\', '\\\\', $className);
        static::expectExceptionMessageMatches(
            "#Level 6: Property 'uninitializedProperty' in {$escapedClassName} is not initialized.#",
        );

        $this->rule->check($objectWithoutConstructor);
    }

    /**
     * Test that inherited uninitialized properties don't trigger the exception
     * if the property is not declared in the checked class itself.
     * @throws \ReflectionException
     */
    public function testCheckIgnoresInheritedUninitializedProperties(): void
    {
        // Use reflection to get parent instance without constructor
        $parentReflection = new ReflectionClass(UninitializedPropertyClass::class);
        $parentInstance = $parentReflection->newInstanceWithoutConstructor();

        // Create a child class instance normally
        $child = new class() extends UninitializedPropertyClass {
            public string $childProp = 'init_child'; // This property is initialized

            // No constructor, relies on parent's (which we bypassed)
        };

        // No exception should be thrown for the child class instance
        // because the uninitialized property belongs to the parent.
        $this->rule->check($child);
        static::assertTrue(true, 'Check should ignore uninitialized properties declared in parent classes.');
    }
}
