<?php

declare(strict_types=1);

namespace Waffle\Security\Rule;

use ReflectionObject;
use Waffle\Exception\SecurityException;
use Waffle\Interface\SecurityRuleInterface;
use Waffle\Trait\ReflectionTrait;

class Level9Rule implements SecurityRuleInterface
{
    use ReflectionTrait;

    /**
     * Security Level 9: Immutability.
     * [Rule 9]: Ensures classes are read-only (simulating the PHP 8.2+ `readonly` attribute for services/DTOs).
     * @throws SecurityException
     */
    public function check(object $object): void
    {
        $reflection = new ReflectionObject($object);

        $isFrameworkComponent = str_starts_with($reflection->getName(), 'Waffle\\');
        $isFrameworkTestComponent = str_starts_with($reflection->getName(), 'WaffleTests\\');
        $isFramework = $isFrameworkComponent || $isFrameworkTestComponent;
        $readonly = $reflection->isReadOnly();

        if ($isFramework && !$readonly && str_contains($reflection->getName(), 'Service')) {
            throw new SecurityException(
                message: 'Level 9: Internal framework service classes must be declared readonly.',
                code: 500,
            );
        }
    }
}
