<?php

declare(strict_types=1);

namespace WaffleTests\Event\Listener;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Waffle\Commons\Contracts\Data\Connection\ConnectionKind;
use Waffle\Event\Listener\OrphanedConnectionListener;
use Waffle\Event\TerminateEvent;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\FixedConnectionTracker;
use WaffleTests\Helper\RecordingLogger;

#[CoversClass(OrphanedConnectionListener::class)]
final class OrphanedConnectionListenerTest extends TestCase
{
    public function testLeakedPdoHandleIsLoggedAsWarning(): void
    {
        $logger = new RecordingLogger();
        $tracker = new FixedConnectionTracker([['id' => 'pdo:1', 'kind' => ConnectionKind::Pdo]]);

        (new OrphanedConnectionListener($tracker, $logger))($this->terminateEvent());

        static::assertCount(1, $logger->records);
        static::assertSame('warning', $logger->records[0]['level']);
        static::assertSame('pdo:1', $logger->records[0]['context']['id'] ?? null);
        static::assertSame('pdo', $logger->records[0]['context']['kind'] ?? null);
    }

    public function testPersistentRedisConnectionIsLoggedAsInfo(): void
    {
        $logger = new RecordingLogger();
        $tracker = new FixedConnectionTracker([['id' => 'redis:9', 'kind' => ConnectionKind::Redis]]);

        (new OrphanedConnectionListener($tracker, $logger))($this->terminateEvent());

        static::assertCount(1, $logger->records);
        static::assertSame('info', $logger->records[0]['level']);
    }

    public function testOpenStreamIsLoggedAsInfo(): void
    {
        $logger = new RecordingLogger();
        $tracker = new FixedConnectionTracker([['id' => 'stream:3', 'kind' => ConnectionKind::Stream]]);

        (new OrphanedConnectionListener($tracker, $logger))($this->terminateEvent());

        static::assertCount(1, $logger->records);
        static::assertSame('info', $logger->records[0]['level']);
    }

    public function testNoOpenConnectionsLogsNothing(): void
    {
        $logger = new RecordingLogger();

        (new OrphanedConnectionListener(new FixedConnectionTracker(), $logger))($this->terminateEvent());

        static::assertSame([], $logger->records);
    }

    public function testMixedKindsAreLoggedAtTheirOwnLevels(): void
    {
        $logger = new RecordingLogger();
        $tracker = new FixedConnectionTracker([
            ['id' => 'pdo:1', 'kind' => ConnectionKind::Pdo],
            ['id' => 'redis:2', 'kind' => ConnectionKind::Redis],
            ['id' => 'stream:3', 'kind' => ConnectionKind::Stream],
        ]);

        (new OrphanedConnectionListener($tracker, $logger))($this->terminateEvent());

        $levels = array_map(static fn(array $record): mixed => $record['level'], $logger->records);
        static::assertSame(['warning', 'info', 'info'], $levels);
    }

    private function terminateEvent(): TerminateEvent
    {
        return new TerminateEvent(
            $this->createStub(ServerRequestInterface::class),
            $this->createStub(ResponseInterface::class),
        );
    }
}
