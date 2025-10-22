<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Security;

final class Rule2ValidObject1
{
    public string $typedProperty = 'hello';
    private $untypedPrivate; // Should be ignored by Level 2
}
