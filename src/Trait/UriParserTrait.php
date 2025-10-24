<?php

declare(strict_types=1);

namespace Waffle\Trait;

// No need to import Constant here

trait UriParserTrait
{
    /**
     * Splits a path string into segments using preg_split for robust handling.
     * Examples:
     * '/' -> ['']
     * '/users/list' -> ['users', 'list']
     * '//api//v1' -> ['api', 'v1'] (empty segments removed)
     * '' -> ['']
     * '///' -> ['']
     *
     * @return string[]
     */
    protected function getPathUri(string $path): array
    {
        // --- Final Logic ---
        // Explicit root and empty string check first
        if ($path === '/' || $path === '') {
            return [''];
        }

        // Split by slash and remove empty segments caused by multiple or trailing/leading slashes (after initial check)
        $segments = preg_split('#/#', $path, -1, PREG_SPLIT_NO_EMPTY);

        // Handle potential preg_split error
        if ($segments === false) {
            return ['']; // Fallback for safety
        }

        // If the result is an empty array (e.g., path was '///'), return ['']
        // This covers cases where the path contained only slashes but wasn't exactly '/'
        $issetSegArray = isset($segments);
        $emptySegArray = $segments === [] || $segments === [''];
        if ($issetSegArray && $emptySegArray && preg_match('#^/+$#', $path)) {
            return [''];
        }

        // Otherwise, return the segments found, or an empty array if none were found (shouldn't happen with above checks)
        return $segments;
    }

    /**
     * Splits the path part of a URI (before '?') into segments.
     * @return string[]
     */
    protected function getRequestUri(string $uri): array
    {
        // Handle query string only case first
        if (str_starts_with($uri, '?')) {
            return ['']; // No path, effectively root
        }

        // Remove query string if present
        $path = strtok($uri, '?');

        // If strtok returns false (e.g., for an empty input string), treat as root.
        if ($path === false || $path === '') {
            return [''];
        }

        // Now, apply the exact same logic as getPathUri to the extracted path
        if ($path === '/') {
            return [''];
        }

        $segments = preg_split('#/#', $path, -1, PREG_SPLIT_NO_EMPTY);

        if ($segments === false) {
            return ['']; // Fallback
        }

        $issetSegArray = isset($segments);
        $emptySegArray = $segments === [] || $segments === [''];
        if ($issetSegArray && $emptySegArray && preg_match('#^/+$#', $path)) {
            return [''];
        }

        return $segments;
    }
}
