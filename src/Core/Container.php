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

    /** @var array<string, string|Closure|object|callable> */
    private array $definitions = [];

    /** @var array<string, true> */
    private array $resolving = [];

    private SecurityInterface $security;

    public function __construct(SecurityInterface $security)
    {
        $this->security = $security;
    }

    /**
     * @throws ContainerException|NotFoundException|ReflectionException
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
            $instance = $this->build($id);
            $this->instances[$id] = $instance;
        } finally {
            unset($this->resolving[$id]);
        }

        return $instance;
    }

    #[\Override]
    public function has(string $id): bool
    {
        return isset($this->definitions[$id]) || class_exists($id);
    }

    /**
     * @param string $id
     * @param object|callable|string $concrete
     */
    #[\Override]
    public function set(string $id, object|callable|string $concrete): void
    {
        $this->definitions[$id] = $concrete;
    }

    /**
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    private function build(string $id): object
    {
        $concrete = $this->definitions[$id] ?? $id;

        /** @var object $instance */
        $instance = match (true) {
            $concrete instanceof Closure => $concrete($this),
            is_string($concrete) && class_exists($concrete) => $this->resolve($concrete),
            default => throw new NotFoundException("Service or class \"{$id}\" not found."),
        };

        $this->security->analyze($instance);

        return $instance;
    }

    /**
     * @throws ContainerException|ReflectionException
     */
    private function resolve(string $class): null|object|string
    {
        $reflector = new ReflectionClass($class);

        if (!$reflector->isInstantiable()) {
            throw new ContainerException("Class \"{$class}\" is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (null === $constructor) {
            return $reflector->newInstance();
        }

        $dependencies = $this->resolveDependencies($constructor->getParameters());

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * @param ReflectionParameter[] $parameters
     * @return array<int, mixed>
     * @throws ContainerException|ReflectionException
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

            /** @var string $name */
            $name = $type->getName();
            $dependencies[] = $this->get(id: $name);
        }

        return $dependencies;
    }
}
