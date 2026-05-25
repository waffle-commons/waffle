<?php

declare(strict_types=1);

namespace Waffle\Event;

use Psr\Http\Message\ServerRequestInterface;

final class ControllerArgumentsResolvedEvent
{
    /**
     * @param array<int, mixed> $arguments
     */
    public function __construct(
        public private(set) ServerRequestInterface $request,
        public private(set) string $controller,
        public private(set) string $method,
        public private(set) array $arguments,
    ) {}
}
