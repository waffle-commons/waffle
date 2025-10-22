<?php

declare(strict_types=1);

namespace WaffleTests\Trait\Helper;

use Waffle\Trait\ReflectionTrait;

class TraitReflection
{
    use ReflectionTrait;

    // Helper methods to call protected trait methods
    public function callClassName(string $path): string
    {
        return $this->className($path);
    }

    public function callNewAttributeInstance(object $className, string $attribute): object
    {
        return $this->newAttributeInstance($className, $attribute);
    }

    public function callControllerValues(null|array $route = null): \Generator
    {
        // Directly yield from the trait's generator method
        yield from $this->controllerValues($route);
    }

    public function callIsFinal(object $object): bool
    {
        return $this->isFinal($object);
    }

    public function callIsInstance(object $object, array $instances): bool
    {
        return $this->isInstance($object, $instances);
    }

    public function callGetProperties(object $object, null|int $filter = null): array
    {
        return $this->getProperties($object, $filter);
    }

    public function callGetMethods(object $object, null|int $filter = null): array
    {
        return $this->getMethods($object, $filter);
    }
}
