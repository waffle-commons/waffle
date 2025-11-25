<?php

declare(strict_types=1);

namespace Waffle\Security\Rule;

use Waffle\Exception\SecurityException;
use Waffle\Interface\SecurityRuleInterface;
use Waffle\Trait\ReflectionTrait;

class Level1Rule implements SecurityRuleInterface
{
    use ReflectionTrait;

    /**
     * Security Level 1: Checks basic object consistency.
     * [Rule 1]: Ensures the object is an instance of its own class.
     * @throws SecurityException
     */
    #[\Override]
    public function check(object $object): void
    {
        $class = get_class($object);
        if (!$this->isInstance(
            object: $object,
            instances: [$class],
        )) {
            // @codeCoverageIgnoreStart
            throw new SecurityException(
                message: "Level 1: Object validation failed. Instance mismatch for {$class}.",
                code: 500,
            );

            // @codeCoverageIgnoreEnd
        }
    }
}
