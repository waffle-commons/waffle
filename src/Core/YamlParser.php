<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Commons\Contracts\Parser\YamlParserInterface;

/**
 * A very simple, native YAML file parser.
 * It supports basic key-value pairs, nesting, and lists.
 */
final class YamlParser implements YamlParserInterface
{
    /**
     * Parses a YAML file and returns its content as a PHP array.
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
        /** @var array<int, array<mixed>> &$stack */
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

            /** @var array<mixed> $currentLevel */
            $currentLevel = &$stack[count($stack) - 1];
            $this->parseLineContent($trimmedLine, $currentLevel, $context);
        }
        return $config;
    }

    /**
     * Manages the stack based on indentation changes.
     * @param array<int, array<array-key, mixed>|object> &$stack
     */
    private function handleIndentation(int $indent, int $lastIndent, array &$stack): void
    {
        if ($indent > $lastIndent) {
            $parent = &$stack[count($stack) - 1];
            if (!is_array($parent)) {
                return;
            }
            end($parent);
            $lastKey = key($parent);

            /** @var array<array-key, mixed> $searchParent */
            $searchParent = $parent;
            if ($lastKey !== null && array_key_exists($lastKey, $searchParent) && is_array($parent[$lastKey])) {
                $stack[] = &$parent[$lastKey];
            }
        } elseif ($indent < $lastIndent) {
            $levelsToPop = ($lastIndent - $indent) / 2;
            for ($i = 0; $i < $levelsToPop; $i++) {
                if (count($stack) > 1) {
                    array_pop($stack);
                }
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
            /** @var string|int|bool|null $value */
            $value = $this->parseValue(substr($line, 2));
            $currentLevel[] = $value;
        } elseif (str_contains($line, ':')) {
            $context = 'key';
            [$key, $value] = explode(':', $line, 2);
            $key = trim($key);
            $trimmedValue = trim($value);

            $currentLevel[$key] = $trimmedValue === '' ? [] : $this->parseValue($trimmedValue);
        }
    }

    private function parseValue(string $value): mixed
    {
        $lowercaseValue = strtolower($value);

        return match (true) {
            str_starts_with($value, '"') && str_ends_with($value, '"'),
            str_starts_with($value, "'") && str_ends_with($value, "'"),
                => substr($value, 1, -1),
            $lowercaseValue === 'true' => true,
            $lowercaseValue === 'false' => false,
            $value === '~', $lowercaseValue === 'null', $value === '' => null,
            is_numeric($value) => str_contains($value, '.') ? (float) $value : (int) $value,
            default => $value,
        };
    }
}
