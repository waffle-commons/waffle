<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Enum\Failsafe;

class Config
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

    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->parameters;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    private function resolveEnvPlaceholders(array &$config): void
    {
        foreach ($config as &$value) {
            if (is_array($value)) {
                $this->resolveEnvPlaceholders($value);
            }
            if (is_string($value) && preg_match('/^%env\((.*)\)%$/', $value, $matches)) {
                $envVar = getenv($matches[1]);
                $value = $envVar ?? null;
            }
        }
    }
}
