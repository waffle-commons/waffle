<?php

declare(strict_types=1);

namespace WaffleTests\Event\Listener;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Waffle\Commons\Contracts\Reactive\BroadcastTransportInterface;
use Waffle\Commons\Contracts\Reactive\MutationRecord;
use Waffle\Event\Listener\BroadcastFlushListener;
use Waffle\Event\TerminateEvent;
use Waffle\Reactive\RequestBroadcastBuffer;
use WaffleTests\AbstractTestCase as TestCase;

#[CoversClass(BroadcastFlushListener::class)]
final class BroadcastFlushListenerTest extends TestCase
{
    public function testFlushPushesEveryBufferedMutation(): void
    {
        $buffer = new RequestBroadcastBuffer();
        $buffer->record(new MutationRecord('orders', 'App\\Order', 'status', 'paid'));
        $buffer->record(new MutationRecord('orders', 'App\\Order', 'total', 42));

        $pushed = [];
        $transport = $this->capturingTransport($pushed);

        (new BroadcastFlushListener($buffer, $transport))($this->terminateEvent());

        static::assertCount(2, $pushed);
        // Buffer was drained.
        static::assertSame([], $buffer->drain());
    }

    public function testFlushIsANoOpWhenNothingWasBuffered(): void
    {
        $buffer = new RequestBroadcastBuffer();
        $pushed = [];
        $transport = $this->capturingTransport($pushed);

        (new BroadcastFlushListener($buffer, $transport))($this->terminateEvent());

        static::assertSame([], $pushed);
    }

    /**
     * @param list<MutationRecord> $sink
     */
    private function capturingTransport(array &$sink): BroadcastTransportInterface
    {
        return new class($sink) implements BroadcastTransportInterface {
            /**
             * @param list<MutationRecord> $sink
             */
            public function __construct(
                private array &$sink,
            ) {}

            #[\Override]
            public function push(MutationRecord $record): void
            {
                $this->sink[] = $record;
            }

            #[\Override]
            public function pushBatch(array $records): void
            {
                foreach ($records as $record) {
                    $this->push($record);
                }
            }
        };
    }

    private function terminateEvent(): TerminateEvent
    {
        return new TerminateEvent(
            $this->createStub(ServerRequestInterface::class),
            $this->createStub(ResponseInterface::class),
        );
    }
}
