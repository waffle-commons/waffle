<?php

declare(strict_types=1);

namespace WaffleTests\Trait\Helper;

use Waffle\Trait\UriParserTrait;

class TraitUri
{
    use UriParserTrait;

    // Expose protected methods publicly for testing
    public function testGetPathUri(string $path): array
    {
        return $this->getPathUri($path);
    }

    public function testGetRequestUri(string $uri): array
    {
        return $this->getRequestUri($uri);
    }
}
