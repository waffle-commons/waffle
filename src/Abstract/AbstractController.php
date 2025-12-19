<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use JsonException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Waffle\Commons\Contracts\Controller\BaseControllerInterface;

abstract class AbstractController implements BaseControllerInterface
{
    protected ResponseFactoryInterface $responseFactory;

    /**
     * Dependency Injection via Setter.
     * Called by the ControllerDispatcher before the action is executed.
     */
    public function setResponseFactory(ResponseFactoryInterface $responseFactory): void
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * Private helper to format JSON response (PSR-7).
     * In the future, this logic should move to AbstractController.
     * @throws JsonException
     */
    protected function jsonResponse(array $data, int $status = 200): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);

        $payload = json_encode($data, JSON_THROW_ON_ERROR);

        $response->getBody()->write($payload);
        $response->getBody()->rewind(); // Good practice for streams

        return $response->withHeader('Content-Type', 'application/json');
    }
}
