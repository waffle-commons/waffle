<?php

declare(strict_types=1);

namespace Waffle\Event;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class TerminateEvent
{
    public function __construct(
        private(set) ServerRequestInterface $request,
        private(set) ResponseInterface $response,
    ) {}

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
