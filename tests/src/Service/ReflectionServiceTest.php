<?php

declare(strict_types=1);

namespace WaffleTests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Waffle\Commons\Contracts\Attribute\Dto;
use Waffle\Service\ReflectionService;
use WaffleTests\Helper\Dto\EmptyDto;
use WaffleTests\Helper\Dto\UserRegistrationDto;

#[CoversClass(ReflectionService::class)]
final class ReflectionServiceTest extends TestCase
{
    private ReflectionService $service;

    #[\Override]
    protected function setUp(): void
    {
        $this->service = new ReflectionService();
    }

    public function testHasAttributeReturnsTrueWhenPresent(): void
    {
        static::assertTrue($this->service->hasAttribute(UserRegistrationDto::class, Dto::class));
    }

    public function testHasAttributeReturnsFalseWhenAbsent(): void
    {
        static::assertFalse($this->service->hasAttribute(stdClass::class, Dto::class));
    }

    public function testHasAttributeReturnsFalseForUnknownClass(): void
    {
        static::assertFalse($this->service->hasAttribute('Waffle\\No\\Such\\Class', Dto::class));
    }

    public function testGetMethodParametersReturnsTheMethodSignature(): void
    {
        $params = $this->service->getMethodParameters(UserRegistrationDto::class, '__construct');

        static::assertCount(2, $params);
        static::assertSame('email', $params[0]->getName());
        static::assertSame('age', $params[1]->getName());
    }

    public function testGetConstructorParametersReturnsParametersWhenPresent(): void
    {
        $params = $this->service->getConstructorParameters(UserRegistrationDto::class);

        static::assertNotNull($params);
        static::assertCount(2, $params);
    }

    public function testGetConstructorParametersReturnsNullWhenClassHasNoConstructor(): void
    {
        static::assertNull($this->service->getConstructorParameters(EmptyDto::class));
    }

    public function testNewInstanceConstructsWithNamedArguments(): void
    {
        $dto = $this->service->newInstance(UserRegistrationDto::class, ['email' => 'a@b.com', 'age' => 20]);

        static::assertInstanceOf(UserRegistrationDto::class, $dto);
        static::assertSame('a@b.com', $dto->email);
        static::assertSame(20, $dto->age);
    }
}
