<?php

declare(strict_types=1);

namespace Waffle\Trait;

use Waffle\Core\Constant;

trait SecurityTrait
{
    /**
     * @param object|null $object
     * @param string[] $expectations
     * @return bool
     */
    public function isValid(?object $object = null, array $expectations = []): bool
    {
        return array_all($expectations, fn($expectation) => $object instanceof $expectation);
    }

    public function isSecure(object $object, int $level = 10): bool
    {
        // TODO: Implement isSecure() method to throw Exceptions.
        $class = get_class(object: $object);

        return match ($level) {
            Constant::SECURITY_LEVEL1,
            Constant::SECURITY_LEVEL2,
            Constant::SECURITY_LEVEL3,
            Constant::SECURITY_LEVEL4,
            Constant::SECURITY_LEVEL5,
            Constant::SECURITY_LEVEL6,
            Constant::SECURITY_LEVEL7,
            Constant::SECURITY_LEVEL8,
            Constant::SECURITY_LEVEL9,
            Constant::SECURITY_LEVEL10 => $object instanceof $class,
            default => true,
        };
    }
}
