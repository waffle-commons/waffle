<?php

declare(strict_types=1);

namespace Waffle\Event;

use Psr\Http\Message\ServerRequestInterface;

final class RequestReceivedEvent
{
    public function __construct(
        private(set) ServerRequestInterface $request,
    ) {}

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function withRequest(ServerRequestInterface $request): self
    {
        return new self($request);
    }
}
