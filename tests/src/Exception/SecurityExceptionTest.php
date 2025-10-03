<?php

declare(strict_types=1);

namespace WaffleTests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Waffle\Exception\SecurityException;
use Waffle\Exception\WaffleException;

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
        $this->assertInstanceOf(WaffleException::class, $exception);
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }
}
