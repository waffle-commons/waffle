<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Security;

class Rule7InvalidObject
{
    public function process(mixed $_untypedArgument): void // Violation
    {
    }
}
