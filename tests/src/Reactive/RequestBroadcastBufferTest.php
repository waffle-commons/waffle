<?php

declare(strict_types=1);

namespace WaffleTests\Reactive;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Commons\Contracts\Reactive\MutationRecord;
use Waffle\Reactive\RequestBroadcastBuffer;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Reactive\Fixture\BroadcastArticle;

#[CoversClass(RequestBroadcastBuffer::class)]
final class RequestBroadcastBufferTest extends TestCase
{
    public function testRecordThenDrainReturnsRecordsInOrderAndEmpties(): void
    {
        $buffer = new RequestBroadcastBuffer();
        $buffer->record(new MutationRecord('orders', 'App\\Order', 'status', 'paid'));
        $buffer->record(new MutationRecord('orders', 'App\\Order', 'total', 42));

        $drained = $buffer->drain();

        static::assertCount(2, $drained);
        $first = $drained[0] ?? null;
        $second = $drained[1] ?? null;
        static::assertInstanceOf(MutationRecord::class, $first);
        static::assertInstanceOf(MutationRecord::class, $second);
        static::assertSame('status', $first->property);
        static::assertSame('total', $second->property);
        // Draining empties the buffer.
        static::assertSame([], $buffer->drain());
    }

    public function testResetEmptiesTheBuffer(): void
    {
        $buffer = new RequestBroadcastBuffer();
        $buffer->record(new MutationRecord('orders', 'App\\Order', 'status', 'paid'));

        $buffer->reset();

        static::assertSame([], $buffer->drain());
    }

    public function testConstructionDoesNotRecordAndOnlyPostHydrationMutationsBroadcast(): void
    {
        $buffer = new RequestBroadcastBuffer();

        // Hydrate first (records nothing — buffer not yet attached), then wire the
        // buffer and mutate. Only the real post-load mutation broadcasts.
        $article = new BroadcastArticle('draft')->withBuffer($buffer);
        static::assertSame([], $buffer->drain(), 'attaching the buffer must not emit a spurious broadcast');

        $article->rename('published');

        $drained = $buffer->drain();
        static::assertCount(1, $drained, 'exactly one mutation — the rename, not the hydration');
        $first = $drained[0] ?? null;
        static::assertInstanceOf(MutationRecord::class, $first);
        static::assertSame('articles', $first->channel);
        static::assertSame(BroadcastArticle::class, $first->entityClass);
        static::assertSame('published', $first->value);
        static::assertSame('published', $article->title);
    }

    public function testWriteHookIsANoOpWithoutABuffer(): void
    {
        $article = new BroadcastArticle('draft');
        $article->rename('published');

        // No buffer attached ⇒ no recording, mutation still applies.
        static::assertSame('published', $article->title);
    }

    public function testEveryPostHydrationMutationIsRecordedInOrder(): void
    {
        $buffer = new RequestBroadcastBuffer();
        $article = new BroadcastArticle('draft')->withBuffer($buffer);

        $article->rename('review');
        $article->rename('published');

        $drained = $buffer->drain();
        static::assertCount(2, $drained);
        $first = $drained[0] ?? null;
        $second = $drained[1] ?? null;
        static::assertInstanceOf(MutationRecord::class, $first);
        static::assertInstanceOf(MutationRecord::class, $second);
        static::assertSame('review', $first->value);
        static::assertSame('published', $second->value);
    }
}
