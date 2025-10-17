<?php

declare(strict_types=1);

namespace Waffle\Security\Rule;

use Waffle\Exception\SecurityException;
use Waffle\Interface\SecurityRuleInterface;
use Waffle\Trait\ReflectionTrait;

class Level6Rule implements SecurityRuleInterface
{
    use ReflectionTrait;

    /**
     * Security Level 6: Reinforces property initialization.
     * [Rule 6]: Ensures all declared properties are initialized (including protected/private).
     * @throws SecurityException
     */
    public function check(object $object): void
    {
        $properties = $this->getProperties(object: $object);
        $class = get_class($object);

        foreach ($properties as $property) {
            if (!$property->isInitialized(object: $object) && $property->getDeclaringClass()->getName() === $class) {
                throw new SecurityException(
                    message: "Level 6: Property '{$property->getName()}' in {$class} is not initialized.",
                    code: 500,
                );
            }
        }
    }
}