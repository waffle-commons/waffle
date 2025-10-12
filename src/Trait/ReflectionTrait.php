<?php

declare(strict_types=1);

namespace Waffle\Trait;

use Generator;
use ReflectionMethod;
use ReflectionObject;

trait ReflectionTrait
{
    public function className(string $path): string
    {
        $content = file_get_contents($path);
        if (false === $content) {
            return ''; // Return empty string on file read error.
        }

        $tokens = token_get_all($content);
        $namespace = '';
        $class = '';
        $tokensCount = count($tokens);

        for ($i = 0; $i < $tokensCount; $i++) {
            if (T_NAMESPACE === $tokens[$i][0]) {
                // Find the full namespace string.
                for ($j = $i + 2; $j < $tokensCount; $j++) {
                    $inArr = in_array($tokens[$j][0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true);
                    if (is_array($tokens[$j]) && $inArr) {
                        $namespace .= $tokens[$j][1];
                    } elseif ('{' === $tokens[$j] || ';' === $tokens[$j]) {
                        break;
                    }
                }
            }

            if (T_CLASS === $tokens[$i][0]) {
                // Find the class name token.
                for ($j = $i + 2; $j < $tokensCount; $j++) {
                    if (T_STRING === $tokens[$j][0]) {
                        $class = $tokens[$j][1];
                        break 2; // Exit both loops once the class is found.
                    }
                }
            }
        }

        if (empty($class)) {
            return ''; // Return empty string if no class is found in the file.
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
    public function controllerValues(null|array $route = null): null|Generator
    {
        if (null === $route) {
            return null;
        }

        foreach ($route as $key => $value) {
            yield $key => $value;
        }
    }
}
