<?php

declare(strict_types=1);

namespace WaffleTests\Exception;

use PHPUnit\Framework\TestCase;
use Waffle\Exception\WaffleException;

final class WaffleExceptionTest extends TestCase
{
    public function testSerializeReturnsCorrectStructureAndData(): void
    {
        // --- Test Condition ---
        // We create a previous exception to simulate a chained exception scenario.
        $previousException = new \Exception('Root cause');
        // We instantiate our custom exception with a message, code, and the previous exception.
        $waffleException = new WaffleException(
            message: 'A waffle error occurred',
            code: 500,
            previous: $previousException
        );

        // --- Execution ---
        // We call the serialize method that we want to test.
        $serializedData = $waffleException->serialize();

        // --- Assertions ---
        // We assert that the returned array has the expected structure.
        // @phpstan-ignore method.alreadyNarrowedType
        $this->assertIsArray($serializedData);
        $this->assertArrayHasKey('message', $serializedData);
        $this->assertArrayHasKey('code', $serializedData);
        $this->assertArrayHasKey('previous', $serializedData);

        // We assert that the data within the array is correct.
        $this->assertSame('A waffle error occurred', $serializedData['message']);
        $this->assertSame(500, $serializedData['code']);
        $this->assertSame($previousException, $serializedData['previous']);
    }

    public function testSerializeHandlesNullPreviousException(): void
    {
        // --- Test Condition ---
        // We instantiate the exception without a previous one.
        $waffleException = new WaffleException('Simple error', 404);

        // --- Execution ---
        $serializedData = $waffleException->serialize();

        // --- Assertions ---
        // We assert that the 'previous' key is null, as expected.
        $this->assertNull($serializedData['previous']);
        $this->assertSame('Simple error', $serializedData['message']);
        $this->assertSame(404, $serializedData['code']);
    }
}
