<?php

declare(strict_types=1);

namespace WaffleTests\Helper;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Waffle\Commons\Contracts\Container\ContainerInterface;

class MockContainer implements ContainerInterface, PsrContainerInterface
{
    private array $services = [];
    private array $building = [];

    #[\Override]
    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            // Try to autoload if class exists but not explicitly set
            if (class_exists($id)) {
                return $this->resolve($id);
            }
            throw new class("Service or class \"{$id}\" not found.") extends \Exception implements
                NotFoundExceptionInterface {};
        }

        return $this->resolve($id);
    }

    private function resolve(string $id)
    {
        // Check for circular dependency
        if (isset($this->building[$id])) {
            throw new class('Circular dependency detected') extends \Exception implements
                ContainerExceptionInterface {};
        }

        /** @var mixed $entry */
        $entry = $this->services[$id] ?? $id;

        // If it's already an instance (and not a closure), return it
        if (is_object($entry) && !$entry instanceof \Closure) {
            return $entry;
        }

        $this->building[$id] = true;

        try {
            /** @var mixed $instance */
            $instance = match (true) {
                $entry instanceof \Closure => $entry($this),
                is_string($entry) && class_exists($entry) => $this->instantiate($entry),
                default => $entry,
            };

            // Cache the instance for singleton behavior
            $this->services[$id] = $instance;
        } finally {
            unset($this->building[$id]);
        }

        return $instance;
    }

    private function instantiate(string $class): object
    {
        if (!class_exists($class) && !interface_exists($class)) {
            throw new \InvalidArgumentException("Class or interface \"$class\" does not exist.");
        }
        $reflector = new \ReflectionClass($class);

        if (!$reflector->isInstantiable()) {
            throw new class("Class {$class} is not instantiable") extends \Exception implements
                ContainerExceptionInterface {};
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return $reflector->newInstance();
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->get($type->getName());
                continue;
            }

            if ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();
                continue;
            }

            if ($type && $type->allowsNull()) {
                $dependencies[] = null;
                continue;
            }

            throw new class("Cannot resolve primitive parameter \"{$param->getName()}\".") extends \Exception implements
                ContainerExceptionInterface {};
        }

        return (object) $reflector->newInstanceArgs($dependencies);
    }

    #[\Override]
    public function has(string $id): bool
    {
        return isset($this->services[$id]) || class_exists($id);
    }

    #[\Override]
    public function set(string $id, mixed $concrete): void
    {
        $this->services[$id] = $concrete;
    }
}
