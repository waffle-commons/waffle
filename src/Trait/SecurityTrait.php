<?php

declare(strict_types=1);

namespace Waffle\Trait;

use ReflectionMethod;
use ReflectionNamedType;
use ReflectionObject;
use ReflectionProperty;
use Waffle\Commons\Contracts\Constant\Constant;
use Waffle\Exception\SecurityException;
use Waffle\Security\Rule\Level10Rule;
use Waffle\Security\Rule\Level1Rule;
use Waffle\Security\Rule\Level2Rule;
use Waffle\Security\Rule\Level3Rule;
use Waffle\Security\Rule\Level4Rule;
use Waffle\Security\Rule\Level5Rule;
use Waffle\Security\Rule\Level6Rule;
use Waffle\Security\Rule\Level7Rule;
use Waffle\Security\Rule\Level8Rule;
use Waffle\Security\Rule\Level9Rule;

trait SecurityTrait
{
    /**
     * @param object|null $object
     * @param string[] $expectations
     * @return bool
     */
    public function isValid(null|object $object = null, array $expectations = []): bool
    {
        if (null === $object) {
            return false;
        }

        foreach ($expectations as $expectation) {
            if (!$object instanceof $expectation) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks cumulative security rules based on the specified level.
     * @throws SecurityException
     */
    public function isSecure(object $object, int $level = 10): void
    {
        $rules = [
            1 => new Level1Rule(),
            2 => new Level2Rule(),
            3 => new Level3Rule(),
            4 => new Level4Rule(),
            5 => new Level5Rule(),
            6 => new Level6Rule(),
            7 => new Level7Rule(),
            8 => new Level8Rule(),
            9 => new Level9Rule(),
            10 => new Level10Rule(),
        ];

        for ($i = 1; $i <= $level; ++$i) {
            if (isset($rules[$i])) {
                $rules[$i]->check($object);
            }
        }
    }
}
