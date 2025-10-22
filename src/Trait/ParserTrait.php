<?php

declare(strict_types=1);

namespace Waffle\Trait;

trait ParserTrait
{
    public function parseValue(string $value): mixed
    {
        $lowercaseValue = strtolower($value);

        return match (true) {
            // Handle quoted strings first to prevent parsing their content
            str_starts_with($value, '"') && str_ends_with($value, '"'),
            str_starts_with($value, "'") && str_ends_with($value, "'"),
                => substr($value, 1, -1),
            // Handle strict booleans and nulls
            $lowercaseValue === 'true' => true,
            $lowercaseValue === 'false' => false,
            $value === '~', $lowercaseValue === 'null', $value === '' => null,
            // Handle numbers
            is_numeric($value) => str_contains($value, '.') ? (float) $value : (int) $value,
            // Default to returning the original string value
            default => $value,
        };
    }
}
