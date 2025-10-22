<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Security;

class Rule5ValidObject1
{
    private string $typedPrivate = 'secret';
    public $untypedPublic; // Ignored by Level 5
}
