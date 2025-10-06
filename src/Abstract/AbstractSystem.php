<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Waffle\Core\Security;
use Waffle\Interface\SystemInterface;
use Waffle\Router\Router;
use Waffle\Trait\ReflectionTrait;
use Waffle\Trait\SystemTrait;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
abstract class AbstractSystem implements SystemInterface
{
    use ReflectionTrait;
    use SystemTrait;

    protected(set) Security $security {
        set => $this->security = $value;
    }

    protected(set) object $config {
        set => $this->config = $value;
    }

    protected(set) null|Router $router = null {
        set => $this->router = $value;
    }

    #[\Override]
    public function registerRouter(Router $router): void
    {
        $this->router = $router;
    }
}
