<?php

declare(strict_types=1);

namespace Waffle\Security\Rule;

use ReflectionMethod;
use ReflectionNamedType;
use Waffle\Core\Constant;
use Waffle\Exception\SecurityException;
use Waffle\Interface\SecurityRuleInterface;
use Waffle\Trait\ReflectionTrait;

class Level3Rule implements SecurityRuleInterface
{
    use ReflectionTrait;

    /**
     * Security Level 3: Basic method validation.
     * [Rule 3]: Ensures all public methods are not 'void' if they return something.
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

            /** @var null|ReflectionNamedType $returnType */
            $returnType = $method->getReturnType();

            if (
                null !== $returnType
                && $returnType->getName() === Constant::TYPE_VOID
                && $method->getDeclaringClass()->getName() === $class
            ) {
                throw new SecurityException(
                    message: "Level 3: Public method '{$method->getName()}' in {$class} must not return 'void'.",
                    code: 500,
                );
            }
        }
    }
}
