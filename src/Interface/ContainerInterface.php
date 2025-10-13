<?php

declare(strict_types=1);

namespace Waffle\Interface;

/**
 * Interface for the Waffle service container.
 */
interface ContainerInterface
{
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return mixed No entry was found for **this** identifier.
     */
    public function get(string $id): mixed;

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     */
    public function has(string $id): bool;

    /**
     * Manually registers a service or a factory in the container.
     *
     * @param string $id The service identifier.
     * @param string|callable $concrete The service instance or a callable factory.
     */
    public function set(string $id, callable|string $concrete): void;
}
