<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Interface\YamlParserInterface;
use Waffle\Trait\ParserTrait;

/**
 * A very simple, native YAML file parser.
 * It supports basic key-value pairs, nesting, and lists.
 * It does not support advanced YAML features like anchors, aliases, or multi-line strings.
 */
final class YamlParser implements YamlParserInterface
{
    use ParserTrait;

    /**
     * Parses a YAML file and returns its content as a PHP array.
     *
     * @param string $path The path to the YAML file.
     * @return array<string, mixed> The parsed content.
     */
    #[\Override]
    public function parseFile(string $path): array
    {
        if (!is_readable($path) || !is_file($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if (!$lines) {
            return [];
        }

        return $this->parseLines($lines);
    }

    /**
     * @param string[] $lines
     * @return array<string, mixed>
     */
    private function parseLines(array $lines): array
    {
        $config = [];
        $stack = [&$config];
        $lastIndent = -1;
        $context = 'key'; // Can be 'key' or 'list'

        foreach ($lines as $line) {
            if (trim($line) === '' || str_starts_with(trim($line), '#')) {
                continue;
            }

            $indent = strlen($line) - strlen(ltrim($line));
            $trimmedLine = trim($line);

            $this->handleIndentation($indent, $lastIndent, $stack);
            $lastIndent = $indent;

            $currentLevel = &$stack[count($stack) - 1];
            $this->parseLineContent($trimmedLine, $currentLevel, $context);
        }
        return $config;
    }

    /**
     * Manages the stack based on indentation changes.
     * @param array<int, mixed> $stack
     */
    private function handleIndentation(int $indent, int $lastIndent, array &$stack): void
    {
        if ($indent > $lastIndent) {
            $parent = &$stack[count($stack) - 1];
            end($parent);
            $lastKey = key($parent);
            if (is_array($parent[$lastKey] ?? null)) {
                $stack[] = &$parent[$lastKey];
            }
        }
        if ($indent < $lastIndent) {
            $levelsToPop = ($lastIndent - $indent) / 2;
            for ($i = 0; $i < $levelsToPop; $i++) {
                array_pop($stack);
            }
        }
    }

    /**
     * Parses the content of a single line.
     * @param array<mixed> $currentLevel
     */
    private function parseLineContent(string $line, array &$currentLevel, string &$context): void
    {
        if (str_starts_with($line, '- ')) {
            $context = 'list';
            $value = $this->parseValue(substr($line, 2));
            $currentLevel[] = $value;
        }
        if (str_contains($line, ':')) {
            $context = 'key';
            [$key, $value] = explode(':', $line, 2);
            $key = trim($key);
            $trimmedValue = trim($value);

            $currentLevel[$key] = $trimmedValue === Constant::EMPTY_STRING ? [] : $this->parseValue($trimmedValue);
        }
    }
}
