<?php

declare(strict_types=1);

namespace Waffle\Security\Rule;

use ReflectionObject;
use Waffle\Exception\SecurityException;
use Waffle\Interface\SecurityRuleInterface;
use Waffle\Trait\ReflectionTrait;

class Level8Rule implements SecurityRuleInterface
{
    use ReflectionTrait;

    /**
     * Security Level 8: Code finalization.
     * [Rule 8]: Ensures important classes (e.g., Controllers) are declared final.
     * @throws SecurityException
     */
    public function check(object $object): void
    {
        // We check if the class name contains 'Controller' and if it is not final.
        $reflection = new ReflectionObject(object: $object);
        if (!$reflection->isFinal() && str_contains($reflection->getName(), 'Controller')) {
            throw new SecurityException(
                message: 'Level 8: Controller classes must be declared final to prevent unintended extension.',
                code: 500,
            );
        }
    }
}
