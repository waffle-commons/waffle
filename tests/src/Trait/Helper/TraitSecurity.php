<?php

declare(strict_types=1);

namespace WaffleTests\Trait\Helper;

use Waffle\Trait\SecurityTrait;

/**
 * Helper class to expose methods from SecurityTrait for testing.
 */
class TraitSecurity
{
    use SecurityTrait;

    /**
     * Public wrapper for the protected/trait isValid method.
     * @param object|null $object
     * @param string[] $expectations
     * @return bool
     */
    public function callIsValid(null|object $object, array $expectations): bool
    {
        return $this->isValid($object, $expectations);
    }

    /**
     * Public wrapper for the protected/trait isSecure method.
     * @param object $object
     * @param int $level
     * @throws \Waffle\Exception\SecurityException
     */
    public function callIsSecure(object $object, int $level = 10): void
    {
        $this->isSecure($object, $level);
    }
}
