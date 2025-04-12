<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Waffle\Core\Security;
use Waffle\Interface\SystemInterface;
use Waffle\Router\Router;
use Waffle\Trait\ReflectionTrait;
use Waffle\Trait\SystemTrait;

abstract class AbstractSystem implements SystemInterface
{
    use ReflectionTrait;
    use SystemTrait;

    protected(set) Security $security
        {
            set => $this->security = $value;
        }

    protected(set) object $config
        {
            set => $this->config = $value;
        }

    protected(set) ? Router $router = null
        {
            set => $this->router = $value;
        }

    abstract public function __construct(Security $security);

    public function registerRouter(Router $router): void
    {
        $this->router = $router;
    }
}
