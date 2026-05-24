<?php

declare(strict_types=1);

namespace Waffle\Handler;

use JsonException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Waffle\Commons\Contracts\Handler\ResponseConverterInterface;

final readonly class ControllerResponseConverter implements ResponseConverterInterface
{
    /**
     * @param string $stringResponseCsp Content-Security-Policy applied to controller
     *                                  returns of type `string` (text/html responses).
     *                                  Beta-1 Phase 3 default mitigates reflected XSS
     *                                  by allowing only same-origin loads.
     */
    public function __construct(
        private ResponseFactoryInterface $factory,
        private string $stringResponseCsp = "default-src 'self'",
    ) {}

    /**
     * @throws JsonException
     */
    #[\Override]
    public function convert(mixed $result): ResponseInterface
    {
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        if ($result === null) {
            return $this->factory->createResponse(204);
        }

        if (is_array($result) || $result instanceof \JsonSerializable) {
            $response = $this->factory->createResponse(200)->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode($result, JSON_THROW_ON_ERROR));
            return $response;
        }

        if (is_string($result)) {
            // Beta-1 Phase 3 (Task 3.3): every auto-generated text/html response carries
            // a strict CSP + nosniff floor so that a controller returning user-influenced
            // strings cannot reflect XSS payloads back to the browser. `withAddedHeader`
            // is used (not `withHeader`) so any upstream middleware that already set a
            // stricter policy is preserved verbatim.
            $response = $this->factory
                ->createResponse(200)
                ->withHeader('Content-Type', 'text/html')
                ->withAddedHeader('Content-Security-Policy', $this->stringResponseCsp)
                ->withAddedHeader('X-Content-Type-Options', 'nosniff');
            $response->getBody()->write($result);
            return $response;
        }

        throw new RuntimeException(sprintf(
            'Controller Error: Returned "%s", but no conversion strategy matched.',
            get_debug_type($result),
        ));
    }
}
