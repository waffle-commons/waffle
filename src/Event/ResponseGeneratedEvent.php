<?php

declare(strict_types=1);

namespace Waffle\Event;

use Psr\Http\Message\ResponseInterface;

final class ResponseGeneratedEvent
{
    public function __construct(
        public private(set) ResponseInterface $response,
    ) {}

    public function withResponse(ResponseInterface $response): self
    {
        return new self($response);
    }
}
