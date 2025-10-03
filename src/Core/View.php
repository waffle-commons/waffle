<?php

declare(strict_types=1);

namespace Waffle\Core;

final readonly class View
{
    /**
     * @param array<mixed>|null $data
     */
    public function __construct(
        public ?array $data = null
    ) {
    }
}
