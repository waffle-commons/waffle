<?php

declare(strict_types=1);

namespace Waffle\Interface;

interface YamlParserInterface
{
    public function parseFile(string $path): array;
}
