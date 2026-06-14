<?php

declare(strict_types=1);

namespace Waffle\Event\Listener;

use Psr\Log\LoggerInterface;
use Waffle\Commons\Contracts\Data\Connection\ConnectionKind;
use Waffle\Commons\Contracts\Data\Connection\ConnectionTrackerInterface;
use Waffle\Event\TerminateEvent;

/**
 * DIAG-03: warns about connections still open when the request terminates.
 *
 * Subscribed to {@see TerminateEvent}, which fires after the response has been
 * emitted and the whole middleware stack has unwound but BEFORE the kernel resets
 * request-scoped services — exactly "by the time the middleware stack finishes".
 * It inspects the {@see ConnectionTrackerInterface} ledger and reports anything
 * still open over PSR-3:
 *
 *  - a relational ({@see ConnectionKind::Pdo}) handle still borrowed is a real
 *    worker-mode leak (it was never returned to the pool) ⇒ `warning`;
 *  - a persistent Redis connection or an open stream is expected/transient ⇒
 *    `info`, surfaced for visibility without crying wolf.
 *
 * Dev/observability only: the demo wires it (and the tracker) solely in debug
 * environments, so production carries neither the listener nor the ledger.
 */
final readonly class OrphanedConnectionListener
{
    public function __construct(
        private ConnectionTrackerInterface $tracker,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(TerminateEvent $event): void
    {
        foreach ($this->tracker->openConnections() as $connection) {
            $context = ['id' => $connection['id'], 'kind' => $connection['kind']->value];

            if ($connection['kind'] === ConnectionKind::Pdo) {
                $this->logger->warning(
                    'Orphaned connection {id} ({kind}) was not released by request end — likely a leaked '
                    . 'pool handle in worker mode.',
                    $context,
                );

                continue;
            }

            $this->logger->info('Connection {id} ({kind}) was still open at request end.', $context);
        }
    }
}
