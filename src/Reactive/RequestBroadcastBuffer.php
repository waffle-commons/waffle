<?php

declare(strict_types=1);

namespace Waffle\Reactive;

use Waffle\Commons\Contracts\Reactive\BroadcastBufferInterface;
use Waffle\Commons\Contracts\Reactive\MutationRecord;
use Waffle\Commons\Contracts\Service\ResettableInterface;

/**
 * Request-scoped, in-memory accumulator of broadcast-flagged mutations (REACTIVE-01).
 *
 * `#[Broadcast]` property write-hooks {@see self::record()} mutations here with no
 * I/O during the request; the finish-request {@see \Waffle\Event\Listener\BroadcastFlushListener}
 * {@see self::drain()}s and publishes them after the response.
 *
 * The buffer is the single piece of request-scoped state in the reactive path, so
 * it implements {@see ResettableInterface} DIRECTLY (not merely transitively via
 * {@see BroadcastBufferInterface}) — the shallow worker-safety audit requires the
 * explicit clause — and the kernel empties it after every request.
 */
final class RequestBroadcastBuffer implements BroadcastBufferInterface, ResettableInterface
{
    /** @var list<MutationRecord> */
    private array $records = [];

    #[\Override]
    public function record(MutationRecord $record): void
    {
        $this->records[] = $record;
    }

    /**
     * @return list<MutationRecord>
     */
    #[\Override]
    public function drain(): array
    {
        $records = $this->records;
        $this->records = [];

        return $records;
    }

    #[\Override]
    public function reset(): void
    {
        $this->records = [];
    }
}
