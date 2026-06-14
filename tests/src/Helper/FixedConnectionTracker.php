<?php

declare(strict_types=1);

namespace WaffleTests\Helper;

use Waffle\Commons\Contracts\Data\Connection\ConnectionKind;
use Waffle\Commons\Contracts\Data\Connection\ConnectionTrackerInterface;

/**
 * Connection-tracker test double seeded with a fixed set of open connections.
 */
final class FixedConnectionTracker implements ConnectionTrackerInterface
{
    /**
     * @param list<array{id: string, kind: ConnectionKind}> $open
     */
    public function __construct(
        private array $open = [],
    ) {}

    #[\Override]
    public function trackOpen(string $id, ConnectionKind $kind): void
    {
        $this->open[] = ['id' => $id, 'kind' => $kind];
    }

    #[\Override]
    public function trackClose(string $id): void
    {
        $this->open = array_values(array_filter(
            $this->open,
            static fn(array $connection): bool => $connection['id'] !== $id,
        ));
    }

    #[\Override]
    public function openConnections(): array
    {
        return $this->open;
    }

    #[\Override]
    public function reset(): void
    {
        $this->open = [];
    }
}
