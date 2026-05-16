<?php

declare(strict_types=1);

namespace WaffleTests\Exception\Container;

use Waffle\Commons\Contracts\Container\Exception\ContainerExceptionInterface;
use Waffle\Exception\Container\ContainerException;
use Waffle\Exception\WaffleException;
use WaffleTests\AbstractTestCase as TestCase;

final class ContainerExceptionTest extends TestCase
{
    public function testDefaults(): void
    {
        $e = new ContainerException();

        static::assertSame('Container not found.', $e->getMessage());
        static::assertSame(0, $e->getCode());
        static::assertInstanceOf(WaffleException::class, $e);
        static::assertInstanceOf(ContainerExceptionInterface::class, $e);
    }

    public function testCustomMessageAndCode(): void
    {
        $e = new ContainerException('boom', 42);

        static::assertSame('boom', $e->getMessage());
        static::assertSame(42, $e->getCode());
    }
}
