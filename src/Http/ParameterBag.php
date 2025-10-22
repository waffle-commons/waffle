<?php

declare(strict_types=1);

namespace Waffle\Http;

/**
 * ParameterBag is a container for key/value pairs.
 * It's a simple way to manage collections of data like GET, POST, or SERVER variables.
 */
class ParameterBag
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        protected array $parameters = [],
    ) {}

    /**
     * Returns all parameters.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->parameters;
    }

    /**
     * Returns a parameter by name.
     *
     * @template T
     * @param T $default The default value if the parameter does not exist
     * @return T|string|array<mixed>
     */
    public function get(string $key, mixed $default = null): mixed
    {
        /** @var T|string|array<mixed> $parameter */
        $parameter = $this->parameters[$key];

        return $parameter ?? $default;
    }

    /**
     * Checks if a parameter is defined.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }
}
