<?php

declare(strict_types=1);

namespace Waffle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Route
{
    /**
     * @param string $path
     * @param string|null $name
     * @param array<Argument>|null $arguments
     */
    public function __construct(
        public string $path,
        public ?string $name = null,
        public ?array $arguments = null
    ) {
    }
}
