<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Waffle\Commons\Contracts\Security\SecurityInterface;
use Waffle\Commons\Contracts\System\SystemInterface;
use Waffle\Commons\Utils\Trait\ReflectionTrait;
use Waffle\Trait\SystemTrait;

abstract class AbstractSystem implements SystemInterface
{
    use ReflectionTrait;
    use SystemTrait;

    public SecurityInterface $security {
        set => $this->security = $value;
    }

    protected(set) object $config {
        set => $this->config = $value;
    }
}
