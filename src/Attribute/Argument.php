<?php

declare(strict_types=1);

namespace Waffle\Attribute;

use Attribute;

#[Attribute]
final class Argument
{
    public function __construct(
        public string $classType,
        public string $paramName,
        public bool $required = true,
    ) {
    }
}
