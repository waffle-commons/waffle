<?php

declare(strict_types=1);

namespace Waffle\Security\Rule;

use ReflectionMethod;
use ReflectionNamedType;
use Waffle\Core\Constant;
use Waffle\Exception\SecurityException;
use Waffle\Interface\SecurityRuleInterface;
use Waffle\Trait\ReflectionTrait;

class Level7Rule implements SecurityRuleInterface
{
    use ReflectionTrait;

    /**
     * Security Level 7: Method argument safety.
     * [Rule 7]: Ensures all public method arguments are typed.
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
            if ($method->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            foreach ($method->getParameters() as $param) {
                /** @var null|ReflectionNamedType $paramType */
                $paramType = $param->getType();
                if (
                    null !== $paramType
                    && $paramType->getName() === Constant::TYPE_MIXED
                    && !$param->isDefaultValueAvailable()
                ) {
                    throw new SecurityException(
                        message: "Level 7: Public method '{$method->getName()}' parameter '{$param->getName()}' "
                        . 'must be strictly typed.',
                        code: 500,
                    );
                }
            }
        }
    }
}
