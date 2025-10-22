<?php

declare(strict_types=1);

namespace WaffleTests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Exception\RenderingException;
use Waffle\Exception\WaffleException;
use WaffleTests\AbstractTestCase as TestCase;

#[CoversClass(RenderingException::class)]
final class RenderingExceptionTest extends TestCase
{
    /**
     * Tests that the exception can be instantiated and is a subclass of WaffleException.
     */
    public function testCanBeInstantiated(): void
    {
        $exception = new RenderingException();
        static::assertInstanceOf(RenderingException::class, $exception);
        static::assertInstanceOf(WaffleException::class, $exception);
    }
}
