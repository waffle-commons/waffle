<?php

declare(strict_types=1);

namespace Waffle\Reactive\Sse;

use Closure;
use Waffle\Commons\Contracts\Reactive\BroadcastTransportInterface;
use Waffle\Commons\Contracts\Reactive\MutationRecord;

use function json_encode;
use function preg_replace;
use function sprintf;

/**
 * Server-Sent Events transport for broadcast mutations (REACTIVE-01).
 *
 * Serializes each {@see MutationRecord} into the SSE wire format
 * (`event: <channel>\ndata: <json>\n\n`) and writes it to an injected sink. The
 * sink is a `Closure(string): void` — by default a thin writer to the SSE stream,
 * but injectable so the framing is unit-testable and so an integrator can point
 * it at FrankenPHP's native SSE output or a hub fan-out without an external SDK
 * (a Mercure client, if wanted, ships as its own wrapper component).
 *
 * Stateless: it holds only its sink and writes synchronously when the
 * finish-request flush invokes it.
 */
final readonly class SseBroadcastTransport implements BroadcastTransportInterface
{
    /**
     * @param Closure(string): void $sink Receives each serialized SSE frame.
     */
    public function __construct(
        private Closure $sink,
    ) {}

    #[\Override]
    public function push(MutationRecord $record): void
    {
        ($this->sink)($this->frame($record));
    }

    /**
     * @param list<MutationRecord> $records
     */
    #[\Override]
    public function pushBatch(array $records): void
    {
        foreach ($records as $record) {
            $this->push($record);
        }
    }

    /**
     * Render one mutation as an SSE frame. A value that cannot be JSON-encoded
     * (e.g. a resource) degrades to an empty object rather than throwing — the
     * flush must never break the request teardown.
     *
     * The channel is interpolated RAW into the `event:` line, so it is sanitized
     * first: SSE delimits fields by `\n` and events by `\n\n`, meaning a channel
     * containing a CR/LF (or a NUL) could otherwise smuggle a second `data:`/
     * `event:` field — SSE field/event injection. The payload is JSON-encoded, so
     * it cannot carry a bare newline; only the channel needs guarding.
     */
    private function frame(MutationRecord $record): string
    {
        $payload = json_encode([
            'channel' => $record->channel,
            'entity' => $record->entityClass,
            'property' => $record->property,
            'value' => $record->value,
        ]);

        if ($payload === false) {
            $payload = '{}';
        }

        return sprintf("event: %s\ndata: %s\n\n", $this->sanitizeChannel($record->channel), $payload);
    }

    /**
     * Strip every CR, LF and other C0 control character (incl. NUL) from a
     * channel name so it cannot terminate the `event:` line and inject a further
     * SSE field. Returns the channel with those bytes removed (never `null`).
     */
    private function sanitizeChannel(string $channel): string
    {
        return preg_replace('/[\x00-\x1F\x7F]/', '', $channel) ?? '';
    }
}
