<?php

declare(strict_types=1);

namespace Waffle\Router;

use Waffle\Commons\Contracts\Constant\Constant;
use Waffle\Commons\Utils\Trait\ReflectionTrait;

class ControllerFinder
{
    use ReflectionTrait;

    /**
     * Finds all potential controller class names in a given directory.
     *
     * @param string|false $directory The directory to scan.
     * @return array<array-key, string>|false A list of class names or false on failure.
     */
    public function find(string|false $directory): array|false
    {
        if (!$directory || !is_dir($directory)) {
            return false;
        }

        return $this->scan($directory);
    }

    /**
     * Recursively scans a directory for PHP files.
     *
     * @param string $directory The directory to scan.
     * @return array<array-key, string> A list of class names.
     */
    private function scan(string $directory): array
    {
        $files = [];
        $paths = scandir(directory: $directory);

        if (!$paths) {
            return [];
        }

        foreach ($paths as $path) {
            // Ignore dot files and common system directories
            if ($path === Constant::CURRENT_DIR || $path === Constant::PREVIOUS_DIR) {
                continue;
            }

            // FIX: Explicitly ignore 'vendor' directory to prevent scanning dependencies
            if ($path === 'vendor' || $path === 'var' || str_starts_with($path, '.')) {
                continue;
            }

            $file = $directory . DIRECTORY_SEPARATOR . $path;

            if (is_dir(filename: $file)) {
                $files = array_merge($files, $this->scan(directory: $file));
                continue;
            }

            if (str_contains($path, Constant::PHPEXT)) {
                $className = $this->className(path: $file);
                // Only add if a valid class name was found
                if ($className !== Constant::EMPTY_STRING) {
                    $files[] = $className;
                }
            }
        }

        return $files;
    }
}
