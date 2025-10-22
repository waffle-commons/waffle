<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Security;

class Rule7ValidObject
{
    public function process(string $_name, int $_id, bool $_isActive = true): void
    {
    }

    public function usesMixedWithDefault(mixed $_data = null): void // Allowed if default is present
    {
    }

    protected function protectedMethod($_untyped): void
    {
    } // Ignored
}
