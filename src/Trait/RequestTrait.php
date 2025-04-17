<?php

declare(strict_types=1);

namespace Waffle\Trait;

use Waffle\Core\Constant;

trait RequestTrait
{
    /**
     * @return string[]
     */
    public function getRequestUri(mixed $uri): array
    {
        /** @var string $reqUri */
        $reqUri = !empty($uri) ? $uri : Constant::EMPTY_STRING;
        $url = explode(separator: Constant::QUESTIONMARK, string: $reqUri);

        return explode(separator: Constant::DIR_SEPARATOR, string: $url[0]);
    }

    /**
     * @return string[]
     */
    public function getPathUri(string $path): array
    {
        return explode(separator: Constant::DIR_SEPARATOR, string: $path);
    }
}
