<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Interface\YamlParserInterface;

/**
 * A very simple, native YAML file parser.
 * It supports basic key-value pairs, nesting, and lists.
 * It does not support advanced YAML features like anchors, aliases, or multi-line strings.
 */
final class YamlParser implements YamlParserInterface
{
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
        if ($lines === false) {
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
            $line = trim($line);

            if ($indent > $lastIndent) {
                $parent = &$stack[count($stack) - 1];

                // Get the last key inserted in the parent
                if ($context === 'key') {
                    end($parent);
                    $lastKey = key($parent);
                    if (isset($parent[$lastKey]) && is_array($parent[$lastKey])) {
                        $stack[] = &$parent[$lastKey];
                    }
                } else { // context === 'list'
                    $lastIndex = count($parent) - 1;
                    if (isset($parent[$lastIndex]) && is_array($parent[$lastIndex])) {
                        $stack[] = &$parent[$lastIndex];
                    }
                }
            } elseif ($indent < $lastIndent) {
                $levelsToPop = ($lastIndent - $indent) / 2;
                for ($i = 0; $i < $levelsToPop; $i++) {
                    array_pop($stack);
                }
            }
            $lastIndent = $indent;
            $currentLevel = &$stack[count($stack) - 1];

            if (str_starts_with($line, '- ')) {
                $context = 'list';
                $value = $this->parseValue(substr($line, 2));
                $currentLevel[] = $value;
            } elseif (str_contains($line, ':')) {
                $context = 'key';
                [$key, $value] = explode(':', $line, 2);
                $key = trim($key);
                $trimmedValue = trim($value);

                if (empty($trimmedValue)) {
                    $currentLevel[$key] = [];
                } else {
                    $currentLevel[$key] = $this->parseValue($trimmedValue);
                }
            }
        }
        return $config;
    }

    private function parseValue(string $value): mixed
    {
        // Handle quoted strings
        if (
            str_starts_with($value, '"') && str_ends_with($value, '"')
            || str_starts_with($value, "'") && str_ends_with($value, "'")
        ) {
            return substr($value, 1, -1);
        }

        // Handle boolean true
        if (strtolower($value) === 'true') {
            return true;
        }

        // Handle boolean false
        if (strtolower($value) === 'false') {
            return false;
        }

        // Handle null
        if ($value === '~' || strtolower($value) === 'null' || $value === '') {
            return null;
        }

        // Handle numbers
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }
}
