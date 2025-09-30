<?php

declare(strict_types=1);

namespace WaffleTests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Waffle\Exception\RenderingException;
use Waffle\Exception\WaffleException;

#[CoversClass(RenderingException::class)]
final class RenderingExceptionTest extends TestCase
{
    /**
     * Tests that the exception can be instantiated and is a subclass of WaffleException.
     */
    public function testCanBeInstantiated(): void
    {
        $exception = new RenderingException();
        $this->assertInstanceOf(RenderingException::class, $exception);
        $this->assertInstanceOf(WaffleException::class, $exception);
    }
}
