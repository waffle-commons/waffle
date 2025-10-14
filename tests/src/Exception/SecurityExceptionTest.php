<?php

declare(strict_types=1);

namespace WaffleTests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Exception\SecurityException;
use Waffle\Exception\WaffleException;
use WaffleTests\TestCase;

#[CoversClass(SecurityException::class)]
final class SecurityExceptionTest extends TestCase
{
    /**
     * This test verifies that the exception can be instantiated correctly
     * and that it inherits from the base WaffleException. It also checks if
     * the constructor properly assigns the message and code.
     */
    public function testExceptionBehavior(): void
    {
        // 1. Setup
        $message = 'Security validation failed.';
        $code = 403;

        // 2. Action
        $exception = new SecurityException($message, $code);

        // 3. Assertions
        static::assertInstanceOf(WaffleException::class, $exception);
        static::assertSame($message, $exception->getMessage());
        static::assertSame($code, $exception->getCode());
    }
}
