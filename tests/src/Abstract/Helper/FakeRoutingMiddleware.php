<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Waffle\Commons\Contracts\Routing\Exception\RouteNotFoundExceptionInterface;
use Waffle\Commons\Contracts\Routing\RouterInterface;

/**
 * Test double for CoreRoutingMiddleware.
 */
final readonly class FakeRoutingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private RouterInterface $router,
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $match = $this->router->matchRequest(request: $request);

            if (null === $match) {
                throw new RuntimeException('Route not found.');
            }

            // We enrich the request with the controller and params found by the router.
            $request = $request
                ->withAttribute('_classname', $match->className)
                ->withAttribute('_method', $match->method)
                ->withAttribute('_arguments', $match->arguments)
                ->withAttribute('_path', $match->path)
                ->withAttribute('_name', $match->name)
                ->withAttribute('_params', $match->params);
        } catch (RuntimeException|RouteNotFoundExceptionInterface $e) {
            throw $e;
        }

        return $handler->handle($request);
    }
}
