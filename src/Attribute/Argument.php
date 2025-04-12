<?php

namespace Waffle\Attribute;

use Attribute;

#[Attribute]
class Argument
{
    public function __construct(
        public string $classType,
        public string $paramName,
        public bool $required = true
    ) {
    }
}
