<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Commons\Contracts\View\ViewInterface;

final readonly class View implements ViewInterface
{
    /**
     * @param array<mixed>|null $data
     */
    public function __construct(
        public null|array $data = null,
    ) {}
}
