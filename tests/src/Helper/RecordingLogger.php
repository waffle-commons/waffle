<?php

declare(strict_types=1);

namespace WaffleTests\Helper;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * PSR-3 logger test spy: records every {@see self::log()} call so tests can assert
 * the level/message/context without parsing a stream.
 */
final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{level: mixed, message: string, context: array<array-key, mixed>}> */
    public array $records = [];

    /**
     * @param array<array-key, mixed> $context
     */
    #[\Override]
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}
