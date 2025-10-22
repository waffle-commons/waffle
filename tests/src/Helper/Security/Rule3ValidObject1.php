<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Security;

final class Rule3ValidObject1
{
    public function getString(): string
    {
        return 'hello';
    }

    public function getNothing() // No return type implies mixed, which is allowed implicitly before Level 4
    {
        return null;
    }

    // Private/protected methods are ignored by this rule implicitly via filter
    protected function protectedMethod(): void
    {
    }

    private function privateMethod(): void
    {
    }
}
