<?php

declare(strict_types=1);

namespace Waffle\Event;

use Psr\Http\Message\ServerRequestInterface;

final class RequestReceivedEvent
{
    public function __construct(
        public private(set) ServerRequestInterface $request,
    ) {}

    public function withRequest(ServerRequestInterface $request): self
    {
        return new self($request);
    }
}
