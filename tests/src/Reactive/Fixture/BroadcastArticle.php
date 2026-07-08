<?php

declare(strict_types=1);

namespace WaffleTests\Reactive\Fixture;

use Waffle\Commons\Contracts\Reactive\Attribute\Broadcast;
use Waffle\Commons\Contracts\Reactive\BroadcastBufferInterface;
use Waffle\Commons\Contracts\Reactive\MutationRecord;

/**
 * Mutable, hooked DTO demonstrating the REACTIVE-01 write-hook pattern: a
 * `#[Broadcast]`-flagged property whose `set` hook enqueues a {@see MutationRecord}
 * into the request-scoped buffer with no I/O. Hooked properties cannot be
 * readonly, hence `final class` + `public private(set)`.
 *
 * Recommended pattern — attach the buffer AFTER hydration, never in the
 * constructor. A `set` hook fires for EVERY assignment, including the initial one
 * the constructor (or an ORM hydrator) performs to populate the entity. If the
 * buffer were already wired at that point, simply loading a record would emit a
 * spurious broadcast for state that did not actually change during the request.
 * So the constructor leaves `$buffer` null (the hook is a no-op) and an explicit
 * {@see self::withBuffer()} step wires the buffer once hydration is complete —
 * from then on only real, post-load mutations broadcast.
 */
final class BroadcastArticle
{
    private ?BroadcastBufferInterface $buffer = null;

    #[Broadcast(channel: 'articles')]
    public private(set) string $title {
        set(string $value) {
            $this->buffer?->record(new MutationRecord('articles', self::class, 'title', $value));
            $this->title = $value;
        }
    }

    public function __construct(string $title)
    {
        // No buffer yet ⇒ the initial assignment (hydration) records nothing.
        $this->title = $title;
    }

    /**
     * Attach the request-scoped buffer AFTER construction/hydration so only
     * subsequent mutations broadcast. Returns $this for fluent wiring.
     */
    public function withBuffer(BroadcastBufferInterface $buffer): self
    {
        $this->buffer = $buffer;

        return $this;
    }

    public function rename(string $title): void
    {
        $this->title = $title;
    }
}
