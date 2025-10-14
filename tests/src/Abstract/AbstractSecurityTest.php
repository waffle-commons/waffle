<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use stdClass;
use Waffle\Exception\SecurityException;
use WaffleTests\Abstract\Helper\ConcreteTestSecurity;
use WaffleTests\TestCase;

final class AbstractSecurityTest extends TestCase
{
    public function testAnalyzeSucceedsWithValidObject(): void
    {
        // --- Test Condition ---
        $config = new stdClass();
        $security = new ConcreteTestSecurity($config);
        $validObject = new \DateTime(); // An object that meets the expectation.

        // --- Execution & Assertions ---
        // We expect no exception to be thrown because the object is valid.
        $security->analyze($validObject, [\DateTime::class, \DateTimeInterface::class]);
        static::assertTrue(true, 'No exception was thrown for a valid object.');
    }

    public function testAnalyzeThrowsExceptionForInvalidObject(): void
    {
        // --- Test Condition ---
        $config = new stdClass();
        $security = new ConcreteTestSecurity($config);
        $invalidObject = new stdClass(); // This object does not meet the expectation.

        // --- Assertions ---
        // We expect a SecurityException because the object is not a \DateTime instance.
        static::expectException(SecurityException::class);
        static::expectExceptionMessage('The object stdClass is not valid. It is not an instance of DateTime.');

        // --- Execution ---
        // This call should trigger the exception.
        $security->analyze($invalidObject, [\DateTime::class]);
    }
}
