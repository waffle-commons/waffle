<?php

declare(strict_types=1);

namespace Waffle\Interface;

use Waffle\Exception\SecurityException;

interface SecurityRuleInterface
{
    /**
     * @throws SecurityException
     */
    public function check(object $object): void;
}
