<?php

declare(strict_types=1);

namespace Waffle\Security\Rule;

use ReflectionMethod;
use Waffle\Exception\SecurityException;
use Waffle\Interface\SecurityRuleInterface;
use Waffle\Trait\ReflectionTrait;

class Level4Rule implements SecurityRuleInterface
{
    use ReflectionTrait;

    /**
     * Security Level 4: Strengthens public method typing.
     * [Rule 4]: Ensures all public methods have an explicit return type (non-null).
     * @throws SecurityException
     */
    public function check(object $object): void
    {
        $methods = $this->getMethods(
            object: $object,
            filter: ReflectionMethod::IS_PUBLIC,
        );
        $class = get_class($object);

        foreach ($methods as $method) {
            if ($method->isConstructor() || str_starts_with($method->getName(), '__')) {
                continue;
            }

            if ($method->getReturnType() === null && $method->getDeclaringClass()->getName() === $class) {
                throw new SecurityException(
                    message: "Level 4: Public method '{$method->getName()}' in {$class} must declare a return type.",
                    code: 500,
                );
            }
        }
    }
}
