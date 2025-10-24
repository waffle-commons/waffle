<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Security;

final class Rule4TypedChildUntypedParent extends Rule4UntypedParent
{
    public function typedChildMethod(): string
    {
        return 'ok';
    }
}
