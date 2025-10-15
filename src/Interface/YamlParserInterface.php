<?php

namespace Waffle\Interface;

interface YamlParserInterface
{
    public function parseFile(string $path): array;
}
