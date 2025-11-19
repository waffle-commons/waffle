<?php

declare(strict_types=1);

namespace Waffle\Trait;

use Waffle\Core\Constant;

trait RequestTrait
{
    /**
     * @return string[]
     */
    public function getPathUri(string $path): array
    {
        return explode(
            separator: DIRECTORY_SEPARATOR,
            string: $path,
        );
    }
}
