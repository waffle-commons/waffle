<?php

declare(strict_types=1);

namespace WaffleTests\Event\Listener;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Waffle\Commons\Contracts\Async\DeferredTaskInterface;
use Waffle\Commons\Contracts\Async\TaskRunnerInterface;
use Waffle\Event\Listener\DeferredTaskFlushListener;
use Waffle\Event\TerminateEvent;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\RecordingLogger;

#[CoversClass(DeferredTaskFlushListener::class)]
final class DeferredTaskFlushListenerTest extends TestCase
{
    public function testFlushRunsTheDeferredTaskQueue(): void
    {
        $runner = $this->countingRunner();

        (new DeferredTaskFlushListener($runner))($this->terminateEvent());

        static::assertSame(1, $runner->runs);
    }

    public function testRunnerExceptionDoesNotPropagateAndIsLogged(): void
    {
        // A runner whose run() throws must NOT break finish-request teardown.
        $runner = $this->throwingRunner(new RuntimeException('teardown boom'));
        $logger = new RecordingLogger();

        // No try/catch here: the assertion is simply that __invoke() returns.
        (new DeferredTaskFlushListener($runner, $logger))($this->terminateEvent());

        static::assertCount(1, $logger->records);
        static::assertSame('error', $logger->records[0]['level']);
        static::assertStringContainsString('Deferred-task flush failed', $logger->records[0]['message']);
        static::assertSame('teardown boom', $logger->records[0]['context']['error'] ?? null);
        static::assertSame(RuntimeException::class, $logger->records[0]['context']['exception'] ?? null);
    }

    private function countingRunner(): TaskRunnerInterface
    {
        return new class implements TaskRunnerInterface {
            public int $runs = 0;

            #[\Override]
            public function defer(DeferredTaskInterface $task): void {}

            #[\Override]
            public function run(): void
            {
                ++$this->runs;
            }

            #[\Override]
            public function pending(): int
            {
                return 0;
            }

            #[\Override]
            public function reset(): void {}
        };
    }

    private function throwingRunner(RuntimeException $error): TaskRunnerInterface
    {
        return new class($error) implements TaskRunnerInterface {
            public function __construct(
                private readonly RuntimeException $error,
            ) {}

            #[\Override]
            public function defer(DeferredTaskInterface $task): void {}

            #[\Override]
            public function run(): void
            {
                throw $this->error;
            }

            #[\Override]
            public function pending(): int
            {
                return 0;
            }

            #[\Override]
            public function reset(): void {}
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
