<?php

declare(strict_types=1);

namespace WaffleTests\Reactive\Sse;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Commons\Contracts\Reactive\MutationRecord;
use Waffle\Reactive\Sse\SseBroadcastTransport;
use WaffleTests\AbstractTestCase as TestCase;

#[CoversClass(SseBroadcastTransport::class)]
final class SseBroadcastTransportTest extends TestCase
{
    public function testPushWritesAnSseFrame(): void
    {
        $frames = [];
        $transport = new SseBroadcastTransport(static function (string $frame) use (&$frames): void {
            $frames[] = $frame;
        });

        $transport->push(new MutationRecord('orders', 'App\\Order', 'status', 'paid'));

        static::assertCount(1, $frames);
        static::assertStringContainsString('event: orders', $frames[0]);
        static::assertStringContainsString('"property":"status"', $frames[0]);
        static::assertStringContainsString('"value":"paid"', $frames[0]);
        static::assertStringEndsWith("\n\n", $frames[0]);
    }

    public function testPushBatchWritesEveryRecordInOrder(): void
    {
        $frames = [];
        $transport = new SseBroadcastTransport(static function (string $frame) use (&$frames): void {
            $frames[] = $frame;
        });

        $transport->pushBatch([
            new MutationRecord('a', 'App\\X', 'one', 1),
            new MutationRecord('b', 'App\\Y', 'two', 2),
        ]);

        static::assertCount(2, $frames);
        static::assertStringContainsString('event: a', $frames[0]);
        static::assertStringContainsString('event: b', $frames[1]);
    }

    public function testUnencodableValueDegradesToEmptyObject(): void
    {
        $captured = '';
        $transport = new SseBroadcastTransport(static function (string $frame) use (&$captured): void {
            $captured = $frame;
        });

        // An invalid UTF-8 byte sequence makes json_encode() return false.
        $transport->push(new MutationRecord('orders', 'App\\Order', 'status', "\xB1\x31"));

        static::assertStringContainsString('data: {}', $captured);
    }

    public function testChannelWithNewlinesCannotInjectASecondSseField(): void
    {
        $captured = '';
        $transport = new SseBroadcastTransport(static function (string $frame) use (&$captured): void {
            $captured = $frame;
        });

        // A hostile channel tries to terminate the `event:` line (\n) and the
        // whole event (\r\n\r\n) to smuggle an out-of-band `event:`/`data:` field.
        $transport->push(
            new MutationRecord("orders\nevent: hijack\r\ndata: spoofed\r\n\r\n", 'App\\Order', 'status', 'paid'),
        );

        // The single legitimate frame has EXACTLY one `event:` and one `data:`
        // line, and the only blank-line terminator is the trailing one. Prefix a
        // newline so the leading `event:` is matched by the same `\nevent: ` probe.
        static::assertSame(1, substr_count("\n" . $captured, "\nevent: "), 'no second event: field injected');
        static::assertSame(1, substr_count("\n" . $captured, "\ndata: "), 'no second data: field injected');
        // The control bytes were stripped, so the channel collapses onto one line.
        static::assertStringContainsString('event: ordersevent: hijackdata: spoofed', $captured);
        static::assertStringEndsWith("\n\n", $captured);
        // Exactly one event terminator (the trailing blank line).
        static::assertSame(1, substr_count($captured, "\n\n"));
    }

    public function testChannelWithCarriageReturnAndNulIsStripped(): void
    {
        $captured = '';
        $transport = new SseBroadcastTransport(static function (string $frame) use (&$captured): void {
            $captured = $frame;
        });

        $transport->push(new MutationRecord("or\x00de\rrs", 'App\\Order', 'status', 'paid'));

        static::assertStringStartsWith('event: orders', $captured);
        static::assertStringNotContainsString("\r", $captured);
        static::assertStringNotContainsString("\x00", $captured);
    }
}
