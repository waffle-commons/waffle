<?php

declare(strict_types=1);

namespace WaffleTests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Exception\RouteNotFoundException;
use Waffle\Exception\WaffleException;
use WaffleTests\AbstractTestCase as TestCase;

#[CoversClass(RouteNotFoundException::class)]
final class RouteNotFoundExceptionTest extends TestCase
{
    /**
     * This test verifies that the exception can be instantiated correctly
     * and that it inherits from the base WaffleException. It also checks if
     * the constructor properly assigns the message and code.
     */
    public function testExceptionBehavior(): void
    {
        // 1. Setup
        $message = 'Route not found.';
        $code = 404;

        // 2. Action
        $exception = new RouteNotFoundException($message, $code);

        // 3. Assertions
        static::assertInstanceOf(WaffleException::class, $exception);
        static::assertSame($message, $exception->getMessage());
        static::assertSame($code, $exception->getCode());
    }
}
