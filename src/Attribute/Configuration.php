<?php

declare(strict_types=1);

namespace Waffle\Attribute;

use Attribute;

/**
 * @psalm-suppress PossiblyUnusedProperty
 * @psalm-suppress UndefinedConstant
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Configuration
{
    private(set) string|false $controllerDir {
        set => $this->controllerDir = $value;
    }

    private(set) string|false $serviceDir {
        set => $this->serviceDir = $value;
    }

    private(set) int $securityLevel {
        set => $this->securityLevel = $value;
    }

    public function __construct(
        string $controller = 'app/Controller',
        string $service = 'app/Service',
        int $securityLevel = 10,
    ) {
        /** @psalm-suppress MixedOperand */
        $this->controllerDir = realpath(path: APP_ROOT . DIRECTORY_SEPARATOR . $controller);
        /** @psalm-suppress MixedOperand */
        $this->serviceDir = realpath(path: APP_ROOT . DIRECTORY_SEPARATOR . $service);
        $this->securityLevel = $securityLevel;
    }
}
