<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use JsonException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Waffle\Commons\Contracts\Controller\BaseControllerInterface;
use Waffle\Exception\RenderingException;

abstract class AbstractController implements BaseControllerInterface
{
    protected ResponseFactoryInterface $responseFactory;

    /**
     * Dependency Injection via Setter.
     * Called by the ControllerDispatcher before the action is executed.
     */
    public function setResponseFactory(ResponseFactoryInterface $responseFactory): void
    {
        // @igor-ignore: setter DI wired once per controller instantiation by ControllerDispatcher; per-request, not shared
        $this->responseFactory = $responseFactory;
    }

    /**
     * Private helper to format JSON response (PSR-7).
     * In the future, this logic should move to AbstractController.
     * @throws RenderingException
     */
    protected function jsonResponse(array $data, int $status = 200): ResponseInterface
    {
        try {
            $response = $this->responseFactory->createResponse($status);

            $payload = json_encode($data, JSON_THROW_ON_ERROR);

            $response->getBody()->write($payload);
            $response->getBody()->rewind(); // Good practice for streams

            return $response->withHeader('Content-Type', 'application/json');
        } catch (JsonException $e) {
            throw new RenderingException(message: $e->getMessage(), code: $e->getCode(), previous: $e);
        }
    }
}
