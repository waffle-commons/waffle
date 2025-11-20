<?php

declare(strict_types=1);

namespace WaffleTests\Helper;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Waffle\Interface\ContainerInterface;

class MockContainer implements ContainerInterface, PsrContainerInterface
{
    private array $services = [];
    private array $building = [];

    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            // Try to autoload if class exists but not explicitly set
            if (class_exists($id)) {
                return $this->resolve($id);
            }
            throw new class("Service or class \"$id\" not found.") extends \Exception implements
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

        $entry = $this->services[$id] ?? $id;

        // If it's already an instance (and not a closure), return it
        if (is_object($entry) && !$entry instanceof \Closure) {
            return $entry;
        }

        $this->building[$id] = true;

        try {
            if ($entry instanceof \Closure) {
                $instance = $entry($this);
            } elseif (is_string($entry) && class_exists($entry)) {
                $instance = $this->instantiate($entry);
            } else {
                // Should be a value or something else
                $instance = $entry;
            }

            // Cache the instance for singleton behavior
            $this->services[$id] = $instance;

            return $instance;
        } finally {
            unset($this->building[$id]);
        }
    }

    private function instantiate(string $class): object
    {
        $reflector = new \ReflectionClass($class);

        if (!$reflector->isInstantiable()) {
            throw new class("Class $class is not instantiable") extends \Exception implements
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

        return $reflector->newInstanceArgs($dependencies);
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]) || class_exists($id);
    }

    public function set(string $id, mixed $service): void
    {
        $this->services[$id] = $service;
    }
}
