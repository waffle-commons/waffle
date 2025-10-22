<?php

declare(strict_types=1);

namespace WaffleTests\Trait\Helper;

use Waffle\Trait\ParserTrait;

class TraitParser
{
    use ParserTrait;

    // Expose the trait method publicly for testing
    public function testParseValue(string $value): mixed
    {
        return $this->parseValue($value);
    }
}
