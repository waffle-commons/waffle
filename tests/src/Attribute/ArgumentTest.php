<?php

declare(strict_types=1);

namespace WaffleTests\Attribute;

use Waffle\Attribute\Argument;
use WaffleTests\AbstractTestCase as TestCase;

final class ArgumentTest extends TestCase
{
    public function testConstructorAssignsPropertiesCorrectly(): void
    {
        // --- Test Condition ---
        // Define the parameters for the Argument attribute.
        $classType = 'string';
        $paramName = 'userId';
        $required = true;

        // --- Execution ---
        // Instantiate the Argument attribute.
        $argument = new Argument(
            classType: $classType,
            paramName: $paramName,
            required: $required,
        );

        // --- Assertions ---
        // Verify that the public properties have been assigned correctly.
        static::assertSame($classType, $argument->classType);
        static::assertSame($paramName, $argument->paramName);
        static::assertSame($required, $argument->required);
    }

    public function testConstructorWithDefaultRequiredValue(): void
    {
        // --- Test Condition ---
        $classType = 'int';
        $paramName = 'page';

        // --- Execution ---
        // Instantiate without the 'required' parameter to test its default value.
        $argument = new Argument(
            classType: $classType,
            paramName: $paramName,
        );

        // --- Assertions ---
        // The 'required' property should default to true.
        static::assertTrue($argument->required);
    }
}
