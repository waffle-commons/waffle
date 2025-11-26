<?php

declare(strict_types=1);

namespace Waffle\Security\Rule;

use ReflectionProperty;use Waffle\Commons\Contracts\Security\SecurityRuleInterface;use Waffle\Exception\SecurityException;use Waffle\Trait\ReflectionTrait;

class Level2Rule implements SecurityRuleInterface
{
    use ReflectionTrait;

    /**
     * Security Level 2: Basic property validation.
     * [Rule 2]: Ensures there are no untyped public properties (encourages private/protected properties).
     * @throws SecurityException
     */
    #[\Override]
    public function check(object $object): void
    {
        $properties = $this->getProperties(
            object: $object,
            filter: ReflectionProperty::IS_PUBLIC,
        );
        $class = get_class($object);

        foreach ($properties as $property) {
            if ($property->getType() === null) {
                throw new SecurityException(
                    message: "Level 2: Public property '{$property->getName()}' in {$class} must be typed.",
                    code: 500,
                );
            }
        }
    }
}
