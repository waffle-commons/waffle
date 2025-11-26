<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Commons\Contracts\Config\ConfigInterface;use Waffle\Commons\Contracts\Enum\Failsafe;use Waffle\Exception\InvalidConfigurationException;

class Config implements ConfigInterface
{
    private array $parameters = [];

    public function __construct(string $configDir, string $environment, Failsafe $failsafe = Failsafe::DISABLED)
    {
        if ($failsafe === Failsafe::ENABLED) {
            $this->loadFailsafeDefaults();
            return;
        }

        $this->loadConfigurationFiles($configDir, $environment);
    }

    private function loadFailsafeDefaults(): void
    {
        // Provide minimal, safe defaults for the exception handler to work.
        $this->parameters = [
            'waffle' => [
                'security' => [
                    'level' => 1, // Lowest security level to avoid issues.
                ],
            ],
        ];
    }

    private function loadConfigurationFiles(string $configDir, string $environment): void
    {
        $parser = new YamlParser();
        $baseConfigFile = $configDir . '/app.yaml';
        $envConfigFile = $configDir . '/app_' . $environment . '.yaml';

        if (file_exists($baseConfigFile)) {
            $this->parameters = $parser->parseFile($baseConfigFile);
        }

        if (file_exists($envConfigFile)) {
            $envConfig = $parser->parseFile($envConfigFile);
            $this->parameters = array_replace_recursive($this->parameters, $envConfig);
        }

        $this->resolveEnvPlaceholders($this->parameters);
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function getInt(string $key, null|int $default = null): null|int
    {
        /** @var array|string|int|bool|null $value */
        $value = $this->get(key: $key);

        if (null === $value) {
            return $default;
        }

        if (!is_int($value)) {
            throw new InvalidConfigurationException(sprintf(
                'Configuration key "%s" expects type "int", but got "%s".',
                $key,
                gettype($value),
            ));
        }

        return $value;
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function getString(string $key, null|string $default = null): null|string
    {
        /** @var array|string|int|bool|null $value */
        $value = $this->get(key: $key);

        if (null === $value) {
            return $default;
        }

        if (!is_string($value)) {
            throw new InvalidConfigurationException(sprintf(
                'Configuration key "%s" expects type "string", but got "%s".',
                $key,
                gettype($value),
            ));
        }

        return $value;
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function getArray(string $key, null|array $default = null): null|array
    {
        /** @var array|string|int|bool|null $value */
        $value = $this->get(key: $key);

        if (null === $value) {
            return $default;
        }

        if (!is_array($value)) {
            throw new InvalidConfigurationException(sprintf(
                'Configuration key "%s" expects type "array", but got "%s".',
                $key,
                gettype($value),
            ));
        }

        return $value;
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function getBool(string $key, bool $default = false): bool
    {
        /** @var array|string|int|bool|null $value */
        $value = $this->get(key: $key);

        if (null === $value) {
            return $default;
        }

        if (!is_bool($value)) {
            throw new InvalidConfigurationException(sprintf(
                'Configuration key "%s" expects type "boolean", but got "%s".',
                $key,
                gettype($value),
            ));
        }

        return $value;
    }

    private function get(string $key): mixed
    {
        $keys = explode('.', $key);
        $value = $this->parameters;

        foreach ($keys as $k) {
            // FIX: Add a guard clause to ensure we only traverse arrays.
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return null;
            }
            /** @var array|string|int|bool $value */
            $value = $value[$k];
        }

        return $value;
    }

    private function resolveEnvPlaceholders(array &$config): void
    {
        /** @var array|string|int|bool $value */
        foreach ($config as &$value) {
            if (is_array($value)) {
                $this->resolveEnvPlaceholders($value);
                // Continue to the next item after recursion to avoid processing an array as a string.
                continue;
            } elseif (is_string($value)) {
                $matches = [];
                if (preg_match('/^%env\((.*)\)%$/', $value, $matches)) {
                    $envVar = getenv($matches[1]);
                    // Ensure we only assign string or null, not false from getenv().
                    $value = is_string($envVar) ? $envVar : null;
                }
            }
        }
    }
}
