<?php

declare(strict_types=1);

namespace Waffle\Trait;

use Waffle\Core\Constant;
use Generator;
use ReflectionMethod;
use ReflectionObject;

trait ReflectionTrait
{
    public function className(string $path): string
    {
        $className = str_replace(
            search: [Constant::PHPEXT, APP_ROOT . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR],
            replace: ['', '', '\\'],
            subject: $path
        );

        return ucfirst(string: $className);
    }

    public function newAttributeInstance(object $className, string $attribute): object
    {
        $obj = $object = new ReflectionObject(object: $className);
        foreach ($object->getAttributes(name: $attribute) as $attr) {
            $obj = $attr->newInstance();
        }

        return $obj;
    }

    /**
     * @param object $className
     * @return ReflectionMethod[]
     */
    public function getMethods(object $className): array
    {
        return new ReflectionObject(object: $className)->getMethods();
    }

    /**
     * @param array{
     *      classname: string,
     *      method: non-empty-string,
     *      arguments: array<non-empty-string, string>,
     *      path: string,
     *      name: non-falsy-string
     * }|null $route
     * @return Generator|null
     */
    public function controllerValues(?array $route = null): ?Generator
    {
        if ($route === null) {
            return null;
        }

        foreach ($route as $key => $value) {
            // @phpstan-ignore generator.returnType
            yield $key => $value;
        }
    }
}
