<?php

declare(strict_types=1);

namespace Waffle\Core;

use ReflectionClass;
use ReflectionException;
use Waffle\Exception\Container\ContainerException;
use Waffle\Exception\Container\NotFoundException;
use Waffle\Interface\ContainerInterface;

final class Container implements ContainerInterface
{
    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, Closure> */
    private array $factories = [];

    /** @var array<string, string> */
    private array $definitions = [];

    /** @var array<string, true> */
    private array $resolving = [];

    /**
     * @throws ContainerException
     * @throws NotFoundException
     */
    #[\Override]
    public function get(string $id): object
    {
        // 1. Return shared instance if it exists.
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // 2. Check for circular dependency.
        if (isset($this->resolving[$id])) {
            throw new ContainerException("Circular dependency detected while resolving service \"{$id}\".");
        }
        $this->resolving[$id] = true;

        try {
            // 3. Use factory if it exists.
            if (isset($this->factories[$id])) {
                $instance = $this->factories[$id]($this);
            } else {
                // 4. Determine concrete class to build. It's either an alias from definitions, or the ID itself.
                $concrete = $this->definitions[$id] ?? $id;

                if (!class_exists($concrete)) {
                    throw new NotFoundException("Service or class \"{$concrete}\" not found.");
                }

                // 5. Resolve and create the instance.
                $instance = $this->resolve($concrete);
            }

            // 6. Store as a shared instance.
            $this->instances[$id] = $instance;

            return $instance;
        } finally {
            // 7. Clean up resolving stack.
            unset($this->resolving[$id]);
        }
    }

    #[\Override]
    public function has(string $id): bool
    {
        return (
            isset($this->instances[$id])
            || isset($this->factories[$id])
            || isset($this->definitions[$id])
            || class_exists($id)
        );
    }

    #[\Override]
    public function set(string $id, callable|string $concrete): void
    {
        if (is_callable($concrete)) {
            $this->factories[$id] = $concrete;
        } else {
            $this->definitions[$id] = $concrete;
        }
    }

    /**
     * @param class-string $class
     * @return object
     * @throws ContainerException|ReflectionException
     */
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
     * @param \ReflectionParameter[] $parameters
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
