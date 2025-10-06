<?php

declare(strict_types=1);

namespace WaffleTests\Trait\Helper;

/**
 * A helper class that violates security rule #9 by containing "Service" in its name
 * but not being declared as readonly.
 */
class NonReadonlyTestService
{
}
