<?php

declare(strict_types=1);

namespace Waffle\Security\Rule;

use ReflectionProperty;
use Waffle\Exception\SecurityException;
use Waffle\Interface\SecurityRuleInterface;
use Waffle\Trait\ReflectionTrait;

class Level5Rule implements SecurityRuleInterface
{
    use ReflectionTrait;

    /**
     * Security Level 5: Ensures good encapsulation.
     * [Rule 5]: For example, ensures *private* properties are typed.
     * @throws SecurityException
     */
    #[\Override]
    public function check(object $object): void
    {
        $properties = $this->getProperties(
            object: $object,
            filter: ReflectionProperty::IS_PRIVATE,
        );
        $class = get_class($object);

        foreach ($properties as $property) {
            if ($property->getType() === null && $property->getDeclaringClass()->getName() === $class) {
                throw new SecurityException(
                    message: "Level 5: Private property '{$property->getName()}' in {$class} must be typed.",
                    code: 500,
                );
            }
        }
    }
}
