<?php

declare(strict_types=1);

namespace Waffle\Event\Listener;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use Waffle\Commons\Contracts\Async\TaskRunnerInterface;
use Waffle\Event\TerminateEvent;

/**
 * Finish-request drain of the deferred-task queue (ASYNC-01).
 *
 * Subscribed to {@see TerminateEvent}, which fires after the response is flushed
 * but before the kernel resets request-scoped services. It runs every task the
 * request deferred via the {@see TaskRunnerInterface}, so short post-response
 * work (mail, webhooks, audit writes) executes out of the user-perceived latency
 * path.
 *
 * This listener lives in the kernel package (not `waffle-commons/async`) so the
 * async runner stays contracts-only and never imports the concrete
 * {@see TerminateEvent}. Register it AFTER the broadcast flush — deferred work may
 * be longer than the latency-sensitive real-time push.
 *
 * Defensive teardown: the runner already isolates per-task failures (each task
 * runs in its own Fiber under a timeout), but {@see self::__invoke()} additionally
 * wraps the whole drain in a catch-all. Terminate runs after the response is
 * emitted, so a throwable escaping here cannot reach the client — it would only
 * corrupt the worker's finish-request teardown (and, with a non-isolating runner,
 * break the reset cascade). We therefore log and swallow rather than propagate.
 */
final readonly class DeferredTaskFlushListener
{
    public function __construct(
        private TaskRunnerInterface $runner,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function __invoke(TerminateEvent $event): void
    {
        try {
            $this->runner->run();
        } catch (Throwable $e) {
            // Never let finish-request teardown break terminate().
            $this->logger->error('Deferred-task flush failed during finish-request teardown; tasks were skipped.', [
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
