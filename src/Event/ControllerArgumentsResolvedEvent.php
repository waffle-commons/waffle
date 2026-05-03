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
        private(set) ServerRequestInterface $request,
        private(set) string $controller,
        private(set) string $method,
        private(set) array $arguments,
    ) {}

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return array<int, mixed>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
