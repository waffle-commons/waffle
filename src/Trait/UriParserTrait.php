<?php

declare(strict_types=1);

namespace Waffle\Trait;

trait UriParserTrait
{
    /**
     * @return string[]
     */
    protected function getPathUri(string $path): array
    {
        return explode('/', trim($path, '/'));
    }

    /**
     * @return string[]
     */
    protected function getRequestUri(string $uri): array
    {
        $uri = strtok($uri, '?');
        return explode('/', trim($uri, '/'));
    }
}
