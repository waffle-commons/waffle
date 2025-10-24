<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Security;

final class Rule5TypedChildUntypedParent extends Rule5UntypedPrivateParent
{
    private string $typedPrivateChild = 'ok';
}
