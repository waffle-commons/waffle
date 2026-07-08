<?php

declare(strict_types=1);

namespace Waffle\Event\Listener;

use Waffle\Commons\Contracts\Reactive\BroadcastBufferInterface;
use Waffle\Commons\Contracts\Reactive\BroadcastTransportInterface;
use Waffle\Event\TerminateEvent;

/**
 * Finish-request flush of buffered broadcast mutations (REACTIVE-01).
 *
 * Subscribed to {@see TerminateEvent}, which fires after the response has been
 * emitted but before the kernel resets request-scoped services. It drains the
 * request's {@see BroadcastBufferInterface} and publishes every recorded mutation
 * through the {@see BroadcastTransportInterface} (SSE / Mercure) — keeping the
 * real-time side effects observable and out of the property-assignment hot path.
 *
 * Register it BEFORE the diagnostics listener so the cheap, latency-sensitive
 * push runs first; the buffer also resets itself defensively, so a skipped
 * terminate (non-terminable kernel, early exit) never leaks mutations into the
 * next request.
 */
final readonly class BroadcastFlushListener
{
    public function __construct(
        private BroadcastBufferInterface $buffer,
        private BroadcastTransportInterface $transport,
    ) {}

    public function __invoke(TerminateEvent $event): void
    {
        $records = $this->buffer->drain();
        if ($records === []) {
            return;
        }

        $this->transport->pushBatch($records);
    }
}
