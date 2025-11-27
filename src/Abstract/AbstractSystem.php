<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Waffle\Commons\Contracts\System\SystemInterface;use Waffle\Commons\Security\Security;use Waffle\Router\Router;use Waffle\Trait\ReflectionTrait;use Waffle\Trait\SystemTrait;

abstract class AbstractSystem implements SystemInterface
{
    use ReflectionTrait;
    use SystemTrait;

    public Security $security {
        set => $this->security = $value;
    }

    protected(set) object $config {
        set => $this->config = $value;
    }

    public null|Router $router = null {
        set => $this->router = $value;
    }

    #[\Override]
    public function registerRouter(Router $router): void
    {
        $this->router = $router;
    }

    public function getRouter(): null|Router
    {
        return $this->router;
    }
}
