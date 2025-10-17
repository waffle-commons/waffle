<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use stdClass;
use Waffle\Exception\SecurityException;
use WaffleTests\Abstract\Helper\ConcreteTestSecurity;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Trait\Helper\NonFinalTestController;

final class AbstractSecurityTest extends TestCase
{
    public function testAnalyzeSucceedsWithValidObject(): void
    {
        // --- Test Condition ---
        $config = $this->createAndGetConfig(securityLevel: 1);
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
        $config = $this->createAndGetConfig(securityLevel: 1);
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

    public function testAnalyzeThrowsExceptionForInsecureObject(): void
    {
        // --- Test Condition ---
        // We set a high security level that will trigger a violation.
        $config = $this->createAndGetConfig(securityLevel: 8);
        $security = new ConcreteTestSecurity($config);
        $insecureObject = new NonFinalTestController(); // This controller is not final, violating level 8.

        // --- Assertions ---
        static::expectException(SecurityException::class);
        static::expectExceptionMessage('The object WaffleTests\Trait\Helper\NonFinalTestController is not secure.');

        // --- Execution ---
        // This call should trigger the security exception.
        $security->analyze($insecureObject);
    }
}
