<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Waffle\Commons\Contracts\Security\SecurityInterface;
use Waffle\Commons\Contracts\System\SystemInterface;
use Waffle\Trait\SystemTrait;

abstract class AbstractSystem implements SystemInterface
{
    use SystemTrait;

    protected(set) SecurityInterface $security;

    protected(set) object $config;
}
