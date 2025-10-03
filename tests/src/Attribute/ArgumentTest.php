<?php

declare(strict_types=1);

namespace WaffleTests\Attribute;

use PHPUnit\Framework\TestCase;
use Waffle\Attribute\Argument;

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
            required: $required
        );

        // --- Assertions ---
        // Verify that the public properties have been assigned correctly.
        $this->assertSame($classType, $argument->classType);
        $this->assertSame($paramName, $argument->paramName);
        $this->assertSame($required, $argument->required);
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
            paramName: $paramName
        );

        // --- Assertions ---
        // The 'required' property should default to true.
        $this->assertTrue($argument->required);
    }
}
