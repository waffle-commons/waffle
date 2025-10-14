<?php

declare(strict_types=1);

namespace Waffle\Core;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use Waffle\Exception\Container\ContainerException;
use Waffle\Exception\Container\NotFoundException;
use Waffle\Interface\ContainerInterface;
use Waffle\Interface\SecurityInterface;

final class Container implements ContainerInterface
{
    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, string> */
    private array $definitions = [];

    /** @var array<string, true> */
    private array $resolving = [];

    private SecurityInterface $security {
        get => $this->security;
        set => $this->security = $value;
    }

    public function __construct(SecurityInterface $security)
    {
        $this->security = $security;
    }

    /**
     * @throws ContainerException
     * @throws NotFoundException
     */
    #[\Override]
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->resolving[$id])) {
            throw new ContainerException("Circular dependency detected while resolving service \"{$id}\".");
        }
        $this->resolving[$id] = true;

        try {
            $concrete = $this->definitions[$id] ?? $id;

            $instance = match (true) {
                $concrete instanceof Closure => $concrete($this),
                class_exists($concrete) => $this->resolve($concrete),
                default => throw new NotFoundException("Service or class \"{$id}\" not found."),
            };

            $this->security->analyze($instance);

            $this->instances[$id] = $instance;

            return $instance;
        } finally {
            unset($this->resolving[$id]);
        }
    }

    #[\Override]
    public function has(string $id): bool
    {
        return isset($this->definitions[$id]) || class_exists($id);
    }

    #[\Override]
    public function set(string $id, callable|string $concrete): void
    {
        $this->definitions[$id] = $concrete;
    }

    private function resolve(string $class): object
    {
        try {
            $reflector = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new ContainerException("Failed to reflect class \"{$class}\".", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new ContainerException("Class \"{$class}\" is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (null === $constructor) {
            return new $class();
        }

        $dependencies = $this->resolveDependencies($constructor->getParameters());

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * @param ReflectionParameter[] $parameters
     * @return array<int, object>
     * @throws ContainerException
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new ContainerException("Cannot resolve primitive parameter \"{$parameter->getName()}\".");
            }

            $dependencies[] = $this->get($type->getName());
        }

        return $dependencies;
    }
}
