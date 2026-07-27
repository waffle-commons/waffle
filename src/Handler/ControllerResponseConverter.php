<?php

declare(strict_types=1);

namespace Waffle\Handler;

use JsonException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Waffle\Commons\Contracts\Handler\ResponseConverterInterface;
use Waffle\Commons\Contracts\Telemetry\Enum\SpanKind;
use Waffle\Commons\Contracts\Telemetry\NullTracer;
use Waffle\Commons\Contracts\Telemetry\TracerInterface;

final readonly class ControllerResponseConverter implements ResponseConverterInterface
{
    /**
     * @param string $stringResponseCsp Content-Security-Policy applied to controller
     *                                  returns of type `string` (text/html responses).
     *                                  This is defense-in-depth only: it narrows what an
     *                                  already-injected payload could still do (no inline
     *                                  script execution, no form-action/meta-refresh
     *                                  redirection, no base-uri hijack). It was never
     *                                  sufficient alone against markup injection — the
     *                                  actual prevention is the `htmlspecialchars()`
     *                                  escaping applied to every bare `string` return (see
     *                                  {@see convertResult()}); a controller that
     *                                  deliberately wants unescaped HTML must opt out with
     *                                  {@see RawHtml}.
     */
    public function __construct(
        private ResponseFactoryInterface $factory,
        private string $stringResponseCsp = "default-src 'self'; form-action 'self'; base-uri 'self'",
        private TracerInterface $tracer = new NullTracer(),
    ) {}

    /**
     * @throws JsonException
     */
    #[\Override]
    public function convert(mixed $result): ResponseInterface
    {
        $span = $this->tracer->startSpan('waffle.response.convert', SpanKind::Internal);

        try {
            $response = $this->convertResult($result);
            $span->setAttribute('http.response.status_code', $response->getStatusCode());

            return $response;
        } finally {
            $span->end();
        }
    }

    /**
     * @throws JsonException
     */
    private function convertResult(mixed $result): ResponseInterface
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

        if ($result instanceof RawHtml) {
            // Explicit opt-out: the controller vouches for this markup, so it is
            // written verbatim — same CSP/nosniff floor as the escaped path below.
            return $this->htmlResponse($result->html);
        }

        if (is_string($result)) {
            // A bare `string` return is the common case of a controller echoing back
            // request-influenced text, so it is escaped by default — this is what
            // actually prevents markup injection. Controllers that deliberately want
            // unescaped HTML must opt in via RawHtml above.
            return $this->htmlResponse(htmlspecialchars($result, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }

        throw new RuntimeException(sprintf(
            'Controller Error: Returned "%s", but no conversion strategy matched.',
            get_debug_type($result),
        ));
    }

    /**
     * Builds the `text/html` response shared by the escaped-string and RawHtml
     * paths: strict CSP + nosniff floor (defense-in-depth, not a substitute for
     * escaping — see the constructor docblock), `withAddedHeader` so any upstream
     * middleware's stricter policy is preserved verbatim.
     */
    private function htmlResponse(string $html): ResponseInterface
    {
        $response = $this->factory
            ->createResponse(200)
            ->withHeader('Content-Type', 'text/html')
            ->withAddedHeader('Content-Security-Policy', $this->stringResponseCsp)
            ->withAddedHeader('X-Content-Type-Options', 'nosniff');
        $response->getBody()->write($html);
        return $response;
    }
}
