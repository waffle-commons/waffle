<?php

declare(strict_types=1);

namespace Waffle\Service;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Waffle\Commons\Contracts\Service\ReflectionServiceInterface;

/**
 * Centralizes the reflection access the framework performs on user-land controllers
 * and DTOs (Beta-1 hardening, Roadmap §1.2 — eliminate trait abuse).
 *
 * Stateless and side-effect free; safe to share across FrankenPHP worker requests.
 */
final readonly class ReflectionService implements ReflectionServiceInterface
{
    /**
     * @param class-string $attributeName
     */
    #[\Override]
    public function hasAttribute(string $className, string $attributeName): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        return new ReflectionClass($className)->getAttributes($attributeName) !== [];
    }

    /**
     * @param class-string|object $target
     * @return list<ReflectionParameter>
     */
    #[\Override]
    public function getMethodParameters(string|object $target, string $method): array
    {
        return new ReflectionMethod($target, $method)->getParameters();
    }

    /**
     * Returns the constructor's parameters, or `null` when the class has no constructor.
     *
     * @param class-string $className
     * @return list<ReflectionParameter>|null
     */
    #[\Override]
    public function getConstructorParameters(string $className): ?array
    {
        $constructor = new ReflectionClass($className)->getConstructor();

        return $constructor instanceof ReflectionMethod ? $constructor->getParameters() : null;
    }

    /**
     * Instantiates the class. Named args ([param => value]) are spread so PHP 8.5 named-argument
     * matching applies — callers do not need to know the constructor's positional order.
     *
     * @param class-string $className
     * @param array<string, mixed> $namedArgs
     */
    #[\Override]
    public function newInstance(string $className, array $namedArgs = []): object
    {
        return new ReflectionClass($className)->newInstance(...$namedArgs);
    }
}
