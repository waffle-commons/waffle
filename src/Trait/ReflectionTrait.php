<?php

declare(strict_types=1);

namespace Waffle\Trait;

use Generator;
use ReflectionClass;
use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;
use Waffle\Commons\Contracts\Constant\Constant;

trait ReflectionTrait
{
    public function className(string $path): string
    {
        $matches = [];
        $content = file_get_contents($path);
        if (!$content) {
            return Constant::EMPTY_STRING; // Return empty string on file read error.
        }

        $namespace = Constant::EMPTY_STRING;
        if (preg_match('~^namespace\s+([^;]+);~sm', $content, $matches)) {
            $namespace = $matches[1];
        }

        $class = Constant::EMPTY_STRING;
        if (preg_match('~\bclass\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)~', $content, $matches)) {
            $class = $matches[1];
        }

        if ('' === $class) {
            return Constant::EMPTY_STRING; // Return empty string if no class is found in the file.
        }

        return $namespace ? $namespace . '\\' . $class : $class;
    }

    /**
     * @param object $className
     * @param class-string $attribute
     * @return object
     */
    public function newAttributeInstance(object $className, string $attribute): object
    {
        $obj = new ReflectionObject(object: $className);
        $object = new ReflectionObject(object: $className);
        foreach ($object->getAttributes(name: $attribute) as $attr) {
            $obj = $attr->newInstance();
        }

        return $obj;
    }

    /**
     * @param array{
     *      classname: string,
     *      method: non-empty-string,
     *      arguments: array<non-empty-string, string>,
     *      path: string,
     *      name: non-falsy-string
     * }|null $route
     * @return Generator
     */
    public function controllerValues(null|array $route = null): Generator
    {
        if (null === $route) {
            return;
        }

        foreach ($route as $key => $value) {
            yield $key => $value;
        }
    }

    /**
     * @param class-string[] $instances
     */
    private function isInstance(object $object, array $instances): bool
    {
        foreach ($instances as $instance) {
            if ($object instanceof $instance) {
                return true;
            }
        }

        return false;
    }

    private function isFinal(object $object): bool
    {
        return new ReflectionObject($object)->isFinal();
    }

    /**
     * @param object $object
     * @param int|null $filter
     * @return ReflectionProperty[]
     */
    private function getProperties(object $object, null|int $filter = null): array
    {
        return new ReflectionObject($object)->getProperties(filter: $filter);
    }

    /**
     * @param object $object
     * @param int|null $filter
     * @return ReflectionMethod[]
     */
    private function getMethods(object $object, null|int $filter = null): array
    {
        return new ReflectionObject($object)->getMethods(filter: $filter);
    }
}
