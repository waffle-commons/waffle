<?php

declare(strict_types=1);

namespace Waffle\Interface;

use Waffle\Exception\SecurityException;

interface SecurityInterface
{
    /**
     * @param object $object
     * @param string[] $expectations
     * @return void
     * @throws SecurityException
     */
    public function analyze(object $object, array $expectations = []): void;
}
