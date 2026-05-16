<?php

declare(strict_types=1);

namespace WaffleTests\Exception\Container;

use Waffle\Commons\Contracts\Container\Exception\NotFoundExceptionInterface;
use Waffle\Exception\Container\ContainerException;
use Waffle\Exception\Container\NotFoundException;
use WaffleTests\AbstractTestCase as TestCase;

final class NotFoundExceptionTest extends TestCase
{
    public function testDefaults(): void
    {
        $e = new NotFoundException();

        static::assertSame('Service not found.', $e->getMessage());
        static::assertSame(0, $e->getCode());
        static::assertInstanceOf(ContainerException::class, $e);
        static::assertInstanceOf(NotFoundExceptionInterface::class, $e);
    }

    public function testCustomMessageAndCode(): void
    {
        $e = new NotFoundException('missing service xyz', 7);

        static::assertSame('missing service xyz', $e->getMessage());
        static::assertSame(7, $e->getCode());
    }
}
