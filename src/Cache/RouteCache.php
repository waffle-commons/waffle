<?php

declare(strict_types=1);

namespace Waffle\Cache;

use Waffle\Core\Constant;

class RouteCache
{
    private const string CACHE_FILE = 'waffle_routes_cache.php';

    /**
     * Attempts to load routes from the cache file.
     *
     * @return array<mixed>|null Returns the array of routes if cache is hit, null otherwise.
     */
    public function load(): null|array
    {
        if ($this->isProduction()) {
            $cacheFile = $this->getCacheFilePath();
            if (file_exists(filename: $cacheFile)) {
                /**
                 * @var array<mixed> $routesArray
                 */
                $routesArray = require $cacheFile;
                return $routesArray;
            }
        }

        return null;
    }

    /**
     * @param array<mixed> $routes
     */
    public function save(array $routes): void
    {
        if ($this->isProduction()) {
            $cacheFile = $this->getCacheFilePath();
            $content = '<?php return ' . var_export($routes, true) . ';';
            file_put_contents($cacheFile, $content, LOCK_EX);
        }
    }

    private function isProduction(): bool
    {
        return getenv(Constant::APP_ENV) === Constant::ENV_DEFAULT;
    }

    private function getCacheFilePath(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::CACHE_FILE;
    }
}
