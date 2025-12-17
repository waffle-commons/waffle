<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Waffle\Commons\Contracts\Pipeline\MiddlewareStackInterface;

class FakeMiddlewareStack implements MiddlewareStackInterface
{
    private array $middlewares = [];

    public function add(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function prepend(MiddlewareInterface $middleware): self
    {
        array_unshift($this->middlewares, $middleware);
        return $this;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function createHandler(RequestHandlerInterface $fallbackHandler): RequestHandlerInterface
    {
        // Simple handler creation that chains middlewares
        return new class($this->middlewares, $fallbackHandler) implements RequestHandlerInterface {
            public function __construct(
                private array $middlewares,
                private RequestHandlerInterface $fallbackHandler,
            ) {}

            public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                if (empty($this->middlewares)) {
                    return $this->fallbackHandler->handle($request);
                }

                $middleware = array_shift($this->middlewares);
                return $middleware->process($request, new self($this->middlewares, $this->fallbackHandler));
            }
        };
    }
}
