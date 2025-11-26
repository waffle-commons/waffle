<?php

declare(strict_types=1);

namespace Waffle\Security\Rule;

use Waffle\Commons\Contracts\Security\SecurityRuleInterface;use Waffle\Exception\SecurityException;use Waffle\Trait\ReflectionTrait;

class Level10Rule implements SecurityRuleInterface
{
    use ReflectionTrait;

    /**
     * Security Level 10: The strictest set (Full Strictness).
     * [Rule 10]: Ensures all classes are final.
     * @throws SecurityException
     */
    #[\Override]
    public function check(object $object): void
    {
        if (!$this->isFinal(object: $object)) {
            throw new SecurityException(
                message: 'Level 10: All classes must be declared final.',
                code: 500,
            );
        }
    }
}
