<?php

declare(strict_types=1);

namespace WaffleTests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Commons\Contracts\Exception\Validation\ValidationExceptionInterface;
use Waffle\Exception\ValidationException;
use Waffle\Exception\WaffleException;
use WaffleTests\AbstractTestCase as TestCase;

#[CoversClass(ValidationException::class)]
final class ValidationExceptionTest extends TestCase
{
    public function testDefaultsToCode422AndNoField(): void
    {
        $e = new ValidationException('bad input');

        static::assertSame('bad input', $e->getMessage());
        static::assertSame(422, $e->getCode());
        static::assertNull($e->getField());
        static::assertInstanceOf(WaffleException::class, $e);
        static::assertInstanceOf(ValidationExceptionInterface::class, $e);
    }

    public function testCarriesFieldNameAndCustomCode(): void
    {
        $previous = new \RuntimeException('root');
        $e = new ValidationException(message: 'invalid email', field: 'email', code: 422, previous: $previous);

        static::assertSame('email', $e->getField());
        static::assertSame($previous, $e->getPrevious());
    }
}
