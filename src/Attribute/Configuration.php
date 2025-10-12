<?php

declare(strict_types=1);

namespace Waffle\Attribute;

use Attribute;
use Waffle\Core\Constant;

#[Attribute(Attribute::TARGET_CLASS)]
class Configuration
{
    public string $controllerDir {
        set => $this->controllerDir = $value;
    }

    public string $serviceDir {
        set => $this->serviceDir = $value;
    }

    public int $securityLevel {
        set => $this->securityLevel = $value;
    }

    public function __construct(
        string $controller = 'app/Controller',
        string $service = 'app/Service',
        int $securityLevel = 10,
    ) {
        /** @var string $root */
        $root = APP_ROOT;
        $this->controllerDir = realpath(path: $root . DIRECTORY_SEPARATOR . $controller) ?: Constant::EMPTY_STRING;
        $this->serviceDir = realpath(path: $root . DIRECTORY_SEPARATOR . $service) ?: Constant::EMPTY_STRING;
        $this->securityLevel = $securityLevel;
    }
}
