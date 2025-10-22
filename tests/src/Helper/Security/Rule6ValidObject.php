<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Security;

class Rule6ValidObject
{
    public string $publicProp = 'init';
    protected int $protectedProp;
    private bool $privateProp;

    public function __construct()
    {
        $this->protectedProp = 1;
        $this->privateProp = true;
    }
}
