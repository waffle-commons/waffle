<?php

declare(strict_types=1);

namespace Waffle\Event;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class TerminateEvent
{
    public function __construct(
        public private(set) ServerRequestInterface $request,
        public private(set) ResponseInterface $response,
    ) {}
}
